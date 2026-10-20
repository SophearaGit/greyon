<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Admin image uploads for the CMS (SPA: src/services/engine/uploads.ts).
 *
 * POST   /admin/uploads  multipart `file` (+ optional `folder`) -> 201 {url,path,width,height,size,mime}
 * DELETE /admin/uploads  `path` (must live under uploads/) -> {ok:true}
 *
 * Files land on the `public` disk under uploads/<folder>/<uuid>.<ext>.
 */
class UploadController extends Controller
{
    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'folder' => ['nullable', 'string', 'max:120'],
        ]);

        $file = $validated['file'];
        $folder = $this->sanitizeFolder($validated['folder'] ?? null);

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if (! in_array($ext, self::EXTENSIONS, true)) {
            $ext = 'jpg';
        }

        $filename = Str::uuid()->toString().'.'.$ext;
        $directory = 'uploads/'.$folder;
        $path = $file->storeAs($directory, $filename, 'public');

        if (! $path) {
            throw ValidationException::withMessages([
                'file' => 'Upload failed. Please try again.',
            ]);
        }

        $disk = Storage::disk('public');

        $url = $disk->url($path);
        if (! str_starts_with($url, 'http')) {
            $url = rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
        }

        $width = null;
        $height = null;
        try {
            $full = $disk->path($path);
            if (is_file($full) && ($size = @getimagesize($full))) {
                [$width, $height] = $size;
            }
        } catch (\Throwable) {
            // dimensions are optional metadata
        }

        return response()->json([
            'url' => $url,
            'path' => $path,
            'width' => $width,
            'height' => $height,
            'size' => $disk->size($path),
            'mime' => $disk->mimeType($path) ?: $file->getMimeType(),
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
        ]);

        $path = ltrim($validated['path'], '/');
        if (! str_starts_with($path, 'uploads/') || str_contains($path, '..')) {
            throw ValidationException::withMessages([
                'path' => 'Only paths under uploads/ may be deleted.',
            ]);
        }

        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            $disk->delete($path);
        }

        return response()->json(['ok' => true]);
    }

    private function sanitizeFolder(?string $folder): string
    {
        $folder = trim((string) $folder, '/');
        if ($folder === '') {
            return 'misc';
        }

        $folder = preg_replace('#[^a-zA-Z0-9._/-]+#', '-', $folder) ?? 'misc';
        $folder = preg_replace('#/+#', '/', $folder) ?? 'misc';
        $folder = trim($folder, '/');

        if ($folder === '' || str_contains($folder, '..')) {
            return 'misc';
        }

        return $folder;
    }
}
