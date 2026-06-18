<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicStorageController extends Controller
{
    public function show(string $path): BinaryFileResponse
    {
        $path = str_replace('\\', '/', ltrim($path, '/'));

        abort_if(Str::contains($path, ['..', "\0"]), Response::HTTP_NOT_FOUND);

        $root = realpath(storage_path('app/public'));
        $file = realpath(storage_path('app/public/'.$path));

        abort_unless($root && $file && Str::startsWith($file, $root) && is_file($file), Response::HTTP_NOT_FOUND);

        return response()->file($file, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
