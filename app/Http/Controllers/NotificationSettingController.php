<?php

namespace App\Http\Controllers;

use App\Support\Toast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class NotificationSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // บล็อก Member ไม่ให้เข้าถึงการตั้งค่าการแจ้งเตือน
        $this->middleware(function ($request, $next) {
            if (Auth::user()?->role === 'member') {
                abort(403, 'คุณไม่มีสิทธิ์เข้าถึงส่วนการตั้งค่าการแจ้งเตือน');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $soundPath = public_path('sounds');
        $sounds = [];

        // // ตรวจสอบไฟล์เสียงที่รองรับใน Folder sounds
        if (File::exists($soundPath)) {
            $sounds = collect(File::files($soundPath))
                ->filter(fn($file) => in_array($file->getExtension(), ['mp3', 'wav', 'ogg']))
                ->map(fn($file) => $file->getFilename())
                ->all();
        }

        // // ตรวจสอบการตั้งค่าเสียงปัจจุบันของผู้ใช้งาน
        $currentSound = Auth::user()->notification_sound;

        return view('settings.notifications.index', compact('sounds', 'currentSound'));
    }

    public function updateSound(Request $request)
    {
        $request->validate([
            'notification_sound' => ['required', 'string'],
        ]);

        try {
            $user = Auth::user();
            $user->notification_sound = $request->notification_sound;
            $user->save();

            Log::info('[NotificationSetting::updateSound] sound updated', [
                'sound'    => $request->notification_sound,
                'actor_id' => Auth::id()
            ]);

            return back()->with('toast', Toast::success('บันทึกการตั้งค่าเสียงเรียบร้อย', 1800));
        } catch (\Throwable $e) {
            Log::error('[NotificationSetting::updateSound] failed', [
                'error' => $e->getMessage()
            ]);

            return back()->with('toast', Toast::error('ไม่สามารถบันทึกการตั้งค่าได้', 2500));
        }
    }

    public function destroySound(Request $request)
    {
        $fileName = $request->file_name;
        $filePath = public_path('sounds/' . $fileName);

        // // Safety lock: ป้องกันการลบไฟล์ระบบหรือไฟล์มาตรฐาน
        if ($fileName === 'new-request.mp3') {
            return back()->with('toast', Toast::warning('ไม่สามารถลบไฟล์มาตรฐานได้', 2200));
        }

        try {
            if (File::exists($filePath)) {
                File::delete($filePath);

                Log::warning('[NotificationSetting::destroySound] file deleted', [
                    'file'     => $fileName,
                    'actor_id' => Auth::id()
                ]);

                return back()->with('toast', Toast::success("ลบไฟล์ {$fileName} เรียบร้อย", 1600));
            }

            return back()->with('toast', Toast::error('ไม่พบไฟล์ที่ต้องการลบ', 2000));
        } catch (\Throwable $e) {
            Log::error('[NotificationSetting::destroySound] failed', [
                'file'  => $fileName,
                'error' => $e->getMessage()
            ]);

            return back()->with('toast', Toast::error('ไม่สามารถลบไฟล์ได้', 2500));
        }
    }
}
