<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AttachmentDownloadController extends Controller
{
    /**
     * แสดง/ดาวน์โหลดไฟล์แนบ
     * - ถ้าเป็น public และมี URL จาก disk: redirect ออกไป (CDN/asset URL)
     * - ถ้าเป็น private: ตรวจอายุ แล้ว stream/download จาก storage โดยตรง
     */
    public function show(Attachment $attachment)
    {
        // ถ้ามี Policy ให้เปิดใช้ได้: $this->authorize('view', $attachment);

        $file = $attachment->file;
        abort_unless($file, Response::HTTP_NOT_FOUND);

        // หมดอายุแล้ว (410 Gone)
        if (method_exists($attachment, 'isExpired') && $attachment->isExpired()) {
            abort(Response::HTTP_GONE, 'Attachment expired');
        }

        // Public: ถ้า disk มี URL ให้ redirect ไปเลย
        if (!$attachment->is_private && $file->url) {
            return redirect()->away($file->url, 302);
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), Response::HTTP_NOT_FOUND);

        $downloadName = $attachment->original_name ?: basename($file->path);

        // จะเปลี่ยนเป็น stream/response()->file ก็ได้ตาม use case
        return $disk->download($file->path, $downloadName);
    }
}
