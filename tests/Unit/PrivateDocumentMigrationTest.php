<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

class PrivateDocumentMigrationTest extends TestCase
{
    private string $directory;

    private Filesystem $filesystem;

    private mixed $previousFilesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem;
        $this->previousFilesystem = File::getFacadeRoot();
        File::swap($this->filesystem);
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'erp-private-migration-'.bin2hex(random_bytes(8));
        $this->filesystem->makeDirectory($this->directory);
    }

    protected function tearDown(): void
    {
        File::swap($this->previousFilesystem);
        $this->filesystem->deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_migration_is_repeatable_and_reversible_without_changing_contents(): void
    {
        $this->put('public/document-management/incoming/report.pdf', 'original report');
        $this->put('public/archives/revision.pdf', 'archive revision');
        $this->put('public/archives/.hidden-document', 'hidden document');
        $this->put('public/company-logos/logo.png', 'public logo');

        $this->migrate('public', 'private');
        $this->migrate('public', 'private');

        $this->assertFileDoesNotExist($this->path('public/document-management/incoming/report.pdf'));
        $this->assertSame('original report', file_get_contents($this->path('private/document-management/incoming/report.pdf')));
        $this->assertSame('hidden document', file_get_contents($this->path('private/archives/.hidden-document')));
        $this->assertSame('public logo', file_get_contents($this->path('public/company-logos/logo.png')));

        $this->migrate('private', 'public');

        $this->assertSame('original report', file_get_contents($this->path('public/document-management/incoming/report.pdf')));
        $this->assertSame('archive revision', file_get_contents($this->path('public/archives/revision.pdf')));
        $this->assertFileDoesNotExist($this->path('private/archives/revision.pdf'));
    }

    public function test_identical_collision_removes_the_public_copy(): void
    {
        $this->put('public/archives/same.pdf', 'same bytes');
        $this->put('private/archives/same.pdf', 'same bytes');

        $this->migrate('public', 'private');

        $this->assertFileDoesNotExist($this->path('public/archives/same.pdf'));
        $this->assertSame('same bytes', file_get_contents($this->path('private/archives/same.pdf')));
    }

    public function test_conflicting_collision_stops_before_moving_any_document(): void
    {
        $this->put('public/document-management/first.pdf', 'first');
        $this->put('public/archives/conflict.pdf', 'source');
        $this->put('private/archives/conflict.pdf', 'existing target');

        $this->assertMigrationFails('public', 'private', 'Document content conflict');

        $this->assertSame('first', file_get_contents($this->path('public/document-management/first.pdf')));
        $this->assertFileDoesNotExist($this->path('private/document-management/first.pdf'));
        $this->assertSame('source', file_get_contents($this->path('public/archives/conflict.pdf')));
        $this->assertSame('existing target', file_get_contents($this->path('private/archives/conflict.pdf')));
    }

    public function test_rollback_conflict_preserves_both_versions(): void
    {
        $this->put('private/archives/conflict.pdf', 'private version');
        $this->put('public/archives/conflict.pdf', 'public version');

        $this->assertMigrationFails('private', 'public', 'Document content conflict');

        $this->assertSame('private version', file_get_contents($this->path('private/archives/conflict.pdf')));
        $this->assertSame('public version', file_get_contents($this->path('public/archives/conflict.pdf')));
    }

    public function test_failed_copy_preserves_source_and_removes_partial_file(): void
    {
        $this->put('public/archives/report.pdf', 'complete source');
        File::swap(new class extends Filesystem
        {
            public function copy($path, $target)
            {
                file_put_contents($target, 'partial');

                return false;
            }
        });

        $this->assertMigrationFails('public', 'private', 'Unable to copy document');

        $this->assertSame('complete source', file_get_contents($this->path('public/archives/report.pdf')));
        $this->assertFileDoesNotExist($this->path('private/archives/report.pdf'));
        $this->assertSame([], $this->filesystem->allFiles($this->path('private'), true));
    }

    public function test_corrupt_copy_is_not_published_or_removed_from_source(): void
    {
        $this->put('public/archives/report.pdf', 'complete source');
        File::swap(new class extends Filesystem
        {
            public function copy($path, $target)
            {
                file_put_contents($target, 'corrupt');

                return true;
            }
        });

        $this->assertMigrationFails('public', 'private', 'Document content conflict');

        $this->assertSame('complete source', file_get_contents($this->path('public/archives/report.pdf')));
        $this->assertFileDoesNotExist($this->path('private/archives/report.pdf'));
        $this->assertSame([], $this->filesystem->allFiles($this->path('private'), true));
    }

    public function test_failed_publish_preserves_source_and_cleans_temporary_file(): void
    {
        $this->put('public/archives/report.pdf', 'complete source');
        File::swap(new class extends Filesystem
        {
            public function move($path, $target)
            {
                return false;
            }
        });

        $this->assertMigrationFails('public', 'private', 'Unable to publish migrated document');

        $this->assertSame('complete source', file_get_contents($this->path('public/archives/report.pdf')));
        $this->assertFileDoesNotExist($this->path('private/archives/report.pdf'));
        $this->assertSame([], $this->filesystem->allFiles($this->path('private'), true));
    }

    public function test_failed_source_delete_can_be_resumed_without_losing_data(): void
    {
        $this->put('public/archives/report.pdf', 'complete source');
        File::swap(new class extends Filesystem
        {
            public function delete($paths)
            {
                return false;
            }
        });

        $this->assertMigrationFails('public', 'private', 'Unable to remove migrated source document');

        $this->assertSame('complete source', file_get_contents($this->path('public/archives/report.pdf')));
        $this->assertSame('complete source', file_get_contents($this->path('private/archives/report.pdf')));

        File::swap($this->filesystem);
        $this->migrate('public', 'private');

        $this->assertFileDoesNotExist($this->path('public/archives/report.pdf'));
        $this->assertSame('complete source', file_get_contents($this->path('private/archives/report.pdf')));
    }

    private function migrate(string $from, string $to): void
    {
        $migration = require dirname(__DIR__, 2).'/database/migrations/2026_11_03_000001_move_sensitive_files_to_private_storage.php';
        $method = new ReflectionMethod($migration, 'moveDirectories');
        $method->invoke($migration, $this->path($from), $this->path($to));
    }

    private function assertMigrationFails(string $from, string $to, string $message): void
    {
        try {
            $this->migrate($from, $to);
            $this->fail('The migration should fail without losing the source document.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }

    private function path(string $path): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.$path;
    }

    private function put(string $path, string $content): void
    {
        $path = $this->path($path);
        $this->filesystem->ensureDirectoryExists(dirname($path));
        file_put_contents($path, $content);
    }
}
