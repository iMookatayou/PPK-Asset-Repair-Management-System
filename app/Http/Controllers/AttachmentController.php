<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\MaintenanceRequest as MR;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * [DEPRECATED]
     * เดิมเคยค้นจาก request_id/file_path (non-polymorphic)
     * ปัจจุบันย้ายไปใช้ความสัมพันธ์ polymorphic แล้ว
     */
    public function index(Request $request)
    {
        return response()->json([
            'error'   => 'deprecated',
            'message' => 'This endpoint is no longer supported. Use /maintenance/requests/{req} and its attachments relation.',
        ], 410);
    }

    /**
     * รายการไฟล์แนบของใบงาน (ยังใช้ได้)
     * GET /maintenance/requests/{req}/attachments (ถ้าคุณมี route ตรงนี้)
     */
    public function indexByRequest(MR $req)
    {
        // ใช้ความสัมพันธ์ polymorphic: $req->attachments()
        $list = $req->attachments()
            ->orderByDesc('created_at')
            ->paginate(20);

        // แปลง url ให้ FE ใช้งานได้ทั้ง public/private
        $list->getCollection()->transform(function (Attachment $a) {
            return [
                'id'            => $a->id,
                'url'           => $a->url,        // private => route('attachments.show', ...)
                'filename'      => $a->filename,
                'mime'          => $a->mime,
                'size'          => $a->size,
                'is_private'    => $a->is_private,
                'caption'       => $a->caption,
                'alt_text'      => $a->alt_text,
                'order_column'  => $a->order_column,
                'uploaded_by'   => $a->uploaded_by,
                'created_at'    => $a->created_at,
            ];
        });

        return response()->json($list);
    }

    /**
     * แสดง/ดาวน์โหลดไฟล์แนบ (สำหรับไฟล์ private)
     * Route ตัวอย่าง:
     *   Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])
     *     ->middleware('auth')
     *     ->name('attachments.show');
     *
     * Query:
     *   ?download=1  -> force download (Content-Disposition: attachment)
     */
    public function show(Request $request, Attachment $attachment)
    {
        // public ไม่น่าถูกเรียกถึงจุดนี้ (เพราะ $attachment->url จะชี้ไป public URL อยู่แล้ว)
        if (!$attachment->is_private) {
            $publicUrl = $attachment->url;
            abort_unless($publicUrl, 404);
            return redirect()->away($publicUrl);
        }

        // private: stream จาก disk
        $disk = $attachment->disk ?: 'local';
        $path = $attachment->path;

        abort_unless($path && Storage::disk($disk)->exists($path), 404);

        $stream = Storage::disk($disk)->readStream($path);
        abort_unless($stream !== false, 404);

        $mime     = $attachment->mime ?: 'application/octet-stream';
        $size     = $attachment->size ?: null;
        $filename = $attachment->filename;
        $download = $request->boolean('download', false);

        $headers = [
            'Content-Type'            => $mime,
            'X-Content-Type-Options'  => 'nosniff',
            'Cache-Control'           => 'private, max-age=0, must-revalidate',
        ];

        if ($size) {
            $headers['Content-Length'] = (string) $size;
        }

        // inline หรือ download
        $disposition = $download ? 'attachment' : 'inline';
        $headers['Content-Disposition'] = $disposition.'; filename="'.addslashes($filename).'"';

        return new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, $headers);
    }

    /**
     * [DEPRECATED] non-polymorphic upload endpoint
     */
    public function store(Request $request)
    {
        return response()->json([
            'error'   => 'deprecated',
            'message' => 'This endpoint is no longer supported. Use MaintenanceRequestController@uploadAttachmentFromBlade or the API variant.',
        ], 410);
    }

    /**
     * [DEPRECATED] non-polymorphic delete endpoint
     * การลบควรทำผ่าน:
     *   DELETE /maintenance/requests/{req}/attachments/{attachment}
     *   -> MaintenanceRequestController@destroyAttachment
     */
    public function destroy(Attachment $attachment)
    {
        return response()->json([
            'error'   => 'deprecated',
            'message' => 'This endpoint is no longer supported. Use maintenance.requests.attachments.destroy.',
        ], 410);
    }

    /**
     * [DEPRECATED] non-polymorphic upload-for-request endpoint
     */
    public function storeForRequest(Request $request, MR $req)
    {
        return response()->json([
            'error'   => 'deprecated',
            'message' => 'This endpoint is no longer supported. Use maintenance.requests.attachments.upload.',
        ], 410);
    }
}
