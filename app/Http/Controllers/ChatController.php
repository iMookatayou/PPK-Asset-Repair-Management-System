<?php

namespace App\Http\Controllers;

use App\Models\ChatThread;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
  public function index(Request $r) {
    $q = $r->string('q')->toString();
    $threads = ChatThread::with('author')
      ->when($q, fn($qq)=>$qq->where('title','like',"%$q%"))
      ->latest()->paginate(15);

    return view('chat.index', compact('threads'));
  }

  public function storeThread(Request $r) {
    $data = $r->validate(['title'=>'required|string|max:180']);
    $thread = ChatThread::create([
      'title'=>$data['title'], 'author_id'=>Auth::id()
    ]);
    return redirect()->route('chat.show',$thread);
  }

  public function show(ChatThread $thread) {
    // แรกเข้าโหลด 50 ข้อความล่าสุด
    $messages = $thread->messages()->with('user')->latest()->take(50)->get()->reverse()->values();
    return view('chat.show', compact('thread','messages'));
  }

  // endpoint โหลดเพิ่ม (polling/htmx)
  public function messages(Request $r, ChatThread $thread) {
    $after = $r->integer('after_id'); // ถ้ามีจะโหลดเฉพาะที่ใหม่กว่า
    $query = $thread->messages()->with('user')->oldest();
    if ($after) { $query->where('id','>', $after); }
    return response()->json($query->take(100)->get());
  }

  public function storeMessage(Request $r, ChatThread $thread) {
    abort_if($thread->is_locked, 403, 'Thread locked');
    $data = $r->validate(['body'=>'required|string|max:3000']);
    $msg = ChatMessage::create([
      'thread_id'=>$thread->id, 'user_id'=>Auth::id(), 'body'=>$data['body']
    ]);
    return back();
  }
}
