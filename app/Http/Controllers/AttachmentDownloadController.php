<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AttachmentDownloadController extends Controller
{
    public function show(Attachment $attachment)
    {
        $file = $attachment->file;
        abort_unless($file, Response::HTTP_NOT_FOUND);

        if (method_exists($attachment, 'isExpired') && $attachment->isExpired()) {
            abort(Response::HTTP_GONE, 'ไฟล์แนบหมดอายุแล้ว');
        }

        if (!$attachment->is_private && $file->url) {
            return redirect()->away($file->url, 302);
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), Response::HTTP_NOT_FOUND);

        $downloadName = $attachment->original_name ?: basename($file->path);

        return $disk->download($file->path, $downloadName);
    }
}
