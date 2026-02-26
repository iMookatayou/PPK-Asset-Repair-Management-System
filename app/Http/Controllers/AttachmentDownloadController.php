<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AttachmentDownloadController extends Controller
{
    public function show(Attachment $attachment)
    {
        $file    = $attachment->file;
        $actorId = request()->user()?->id;

        abort_unless($file, Response::HTTP_NOT_FOUND);

        if (method_exists($attachment, 'isExpired') && $attachment->isExpired()) {
            Log::warning('[AttachmentDownload::show] expired attachment', [
                'attachment_id' => $attachment->id,
                'file_id'       => $file?->id,
                'actor_id'      => $actorId,
            ]);
            abort(Response::HTTP_GONE, 'ไฟล์แนบหมดอายุแล้ว');
        }

        // Public file → redirect to URL
        if (!$attachment->is_private && $file->url) {
            Log::info('[AttachmentDownload::show] redirect to public URL', [
                'attachment_id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'actor_id'      => $actorId,
            ]);
            return redirect()->away($file->url, 302);
        }

        // Private file → stream download
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($file->disk);

        if (!$disk->exists($file->path)) {
            Log::error('[AttachmentDownload::show] file not found on disk', [
                'attachment_id' => $attachment->id,
                'file_id'       => $file->id,
                'disk'          => $file->disk,
                'path'          => $file->path,
                'actor_id'      => $actorId,
            ]);
            abort(Response::HTTP_NOT_FOUND);
        }

        $downloadName = $attachment->original_name ?: basename($file->path);

        Log::info('[AttachmentDownload::show] private file downloaded', [
            'attachment_id' => $attachment->id,
            'file_id'       => $file->id,
            'original_name' => $downloadName,
            'size'          => $file->size,
            'actor_id'      => $actorId,
        ]);

        return $disk->download($file->path, $downloadName);
    }
}
