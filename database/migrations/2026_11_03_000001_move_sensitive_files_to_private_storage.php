<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    private const SENSITIVE_DIRECTORIES = ['document-management', 'archives'];

    public function up(): void
    {
        $this->moveDirectories(public_path('user-files'), storage_path('app/private'));
    }

    public function down(): void
    {
        $this->moveDirectories(storage_path('app/private'), public_path('user-files'));
    }

    private function moveDirectories(string $fromRoot, string $toRoot): void
    {
        $files = [];
        $directories = [];

        // Validate every collision before moving any document.
        foreach (self::SENSITIVE_DIRECTORIES as $directory) {
            $source = $fromRoot.DIRECTORY_SEPARATOR.$directory;

            if (! File::isDirectory($source)) {
                continue;
            }

            $directories[] = $source;

            foreach (File::allFiles($source, true) as $file) {
                $path = $file->getPathname();
                $target = $toRoot.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$file->getRelativePathname();
                $checksum = $this->checksum($path);

                if (File::exists($target) || is_link($target)) {
                    $this->assertMatchingFile($target, $checksum);
                }

                $files[] = [$path, $target, $checksum];
            }
        }

        foreach ($files as [$source, $target, $checksum]) {
            $this->assertMatchingFile($source, $checksum);
            File::ensureDirectoryExists(dirname($target));

            if (File::exists($target) || is_link($target)) {
                $this->assertMatchingFile($target, $checksum);
            } else {
                $temporary = $target.'.migration-'.bin2hex(random_bytes(8)).'.tmp';

                try {
                    if (! File::copy($source, $temporary)) {
                        throw new RuntimeException("Unable to copy document: {$source}");
                    }

                    $this->assertMatchingFile($temporary, $checksum);

                    // Never replace a destination created since the initial inventory.
                    if (File::exists($target) || is_link($target)) {
                        $this->assertMatchingFile($target, $checksum);
                    } elseif (! File::move($temporary, $target)) {
                        throw new RuntimeException("Unable to publish migrated document: {$target}");
                    }
                } finally {
                    if (File::exists($temporary)) {
                        File::delete($temporary);
                    }
                }
            }

            // Keep the source until a complete, identical destination is verified.
            $this->assertMatchingFile($target, $checksum);
            $this->assertMatchingFile($source, $checksum);

            if (! File::delete($source) || File::exists($source)) {
                throw new RuntimeException("Unable to remove migrated source document: {$source}");
            }
        }

        foreach ($directories as $directory) {
            if (File::isDirectory($directory) && count(File::allFiles($directory, true)) === 0
                && ! File::deleteDirectory($directory)) {
                throw new RuntimeException("Unable to remove empty source directory: {$directory}");
            }
        }
    }

    private function checksum(string $path): string
    {
        if (! File::isFile($path) || ! is_readable($path)) {
            throw new RuntimeException("Document is not a readable file: {$path}");
        }

        $checksum = File::hash($path, 'sha256');

        if ($checksum === false) {
            throw new RuntimeException("Unable to checksum document: {$path}");
        }

        return $checksum;
    }

    private function assertMatchingFile(string $path, string $checksum): void
    {
        if (! hash_equals($checksum, $this->checksum($path))) {
            throw new RuntimeException("Document content conflict; no file overwritten: {$path}");
        }
    }
};
