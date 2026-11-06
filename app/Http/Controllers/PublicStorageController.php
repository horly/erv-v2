<?php

namespace App\Http\Controllers;

use App\Models\ArchiveRecord;
use App\Models\CompanySite;
use App\Models\DocumentManagementRecord;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicStorageController extends Controller
{
    public function show(string $path): BinaryFileResponse
    {
        $path = $this->sanitizePath($path);

        $root = realpath(storage_path('app/public'));
        $file = realpath(storage_path('app/public/'.$path));

        abort_unless($root && $file && Str::startsWith($file, $root) && is_file($file), Response::HTTP_NOT_FOUND);

        return response()->file($file, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    public function showPrivate(string $path): BinaryFileResponse
    {
        $path = $this->sanitizePath($path);

        $site = $this->resolveOwningSite($path);
        abort_unless($site, Response::HTTP_NOT_FOUND);

        /** @var User&Authenticatable $user */
        $user = Auth::user();
        abort_unless($user && $this->userCanAccessSite($user, $site), Response::HTTP_NOT_FOUND);

        $root = realpath(storage_path('app/private'));
        $file = realpath(storage_path('app/private/'.$path));

        abort_unless($root && $file && Str::startsWith($file, $root) && is_file($file), Response::HTTP_NOT_FOUND);

        return response()->file($file)->setCache([
            'no_store' => true,
            'private' => true,
        ]);
    }

    private function sanitizePath(string $path): string
    {
        $path = str_replace('\\', '/', ltrim($path, '/'));

        abort_if(Str::contains($path, ['..', "\0"]), Response::HTTP_NOT_FOUND);

        return $path;
    }

    private function resolveOwningSite(string $path): ?CompanySite
    {
        if (Str::startsWith($path, 'document-management/')) {
            return DocumentManagementRecord::query()
                ->with('site.company')
                ->where('file_path', $path)
                ->first()
                ?->site;
        }

        if (Str::startsWith($path, 'archives/')) {
            $record = ArchiveRecord::query()
                ->with('site.company')
                ->where('file_path', $path)
                ->first();

            if (! $record) {
                $recordId = DB::table('archive_record_file_revisions')
                    ->where('old_file_path', $path)
                    ->orWhere('new_file_path', $path)
                    ->value('archive_record_id');
                $record = $recordId
                    ? ArchiveRecord::query()->with('site.company')->find($recordId)
                    : null;
            }

            return $record?->site;
        }

        return null;
    }

    private function userCanAccessSite(User&Authenticatable $user, CompanySite $site): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $user->subscription_id !== null
                && $site->company?->subscription_id === $user->subscription_id;
        }

        return $user->isUser()
            && $user->sites()->whereKey($site->getKey())->exists();
    }
}
