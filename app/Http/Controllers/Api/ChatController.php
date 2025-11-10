<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    public function index(Request $r)
    {
        $q = (string) $r->query('q', '');
        $userId = optional($r->user())->id;

        $threads = ChatThread::query()
            ->with('author:id,name')
            ->withCount('messages')
            ->with(['latestMessage' => fn($qq) => $qq->with('user:id,name')])
            ->when($q !== '', fn($qq) => $qq->where('title', 'like', "%{$q}%"))
            ->orderByDesc('created_at')
            ->paginate(perPage: 15);

        $readsMap = [];
        if ($userId) {
            $readsMap = DB::table('chat_thread_reads')
                ->where('user_id', $userId)
                ->whereIn('chat_thread_id', $threads->getCollection()->pluck('id')->all())
                ->pluck('last_read_message_id', 'chat_thread_id')
                ->all();
        }

        $payload = [
            'data' => $threads->getCollection()->map(function (ChatThread $th) use ($readsMap) {
                $lastReadMessageId = $readsMap[$th->id] ?? null;
                $total   = $th->messages_count ?? 0;
                $unread  = 0;
                if ($lastReadMessageId) {
                    $unread = \App\Models\ChatMessage::query()
                        ->where('chat_thread_id', $th->id)
                        ->where('id', '>', $lastReadMessageId)
                        ->count();
                } else {
                    $unread = $total;
                }
                return [
                    'id'              => $th->id,
                    'title'           => $th->title,
                    'is_locked'       => (bool) $th->is_locked,
                    'created_at'      => $th->created_at?->toISOString(),
                    'author'          => $th->author ? [
                        'id'   => $th->author->id,
                        'name' => $th->author->name,
                    ] : null,
                    'messages_count'  => $total,
                    'unread_count'    => $unread,
                    'latest_message'  => $th->latestMessage ? [
                        'id'         => $th->latestMessage->id,
                        'user'       => $th->latestMessage->user ? [
                            'id'   => $th->latestMessage->user->id,
                            'name' => $th->latestMessage->user->name,
                        ] : null,
                        'body'       => $th->latestMessage->body,
                        'created_at' => $th->latestMessage->created_at?->toISOString(),
                    ] : null,
                ];
            }),
            'meta' => [
                'current_page' => $threads->currentPage(),
                'per_page'     => $threads->perPage(),
                'total'        => $threads->total(),
                'last_page'    => $threads->lastPage(),
            ],
        ];

        return response()->json($payload);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'title' => ['required', 'string', 'max:180'],
        ]);

        $thread = ChatThread::create([
            'title'     => $data['title'],
            'author_id' => Auth::id(),
            'is_locked' => false,
        ]);

        return response()->json([
            'id'         => $thread->id,
            'title'      => $thread->title,
            'is_locked'  => (bool) $thread->is_locked,
            'created_at' => $thread->created_at?->toISOString(),
        ], 201);
    }

    public function show(ChatThread $thread)
    {
        $thread->load('author:id,name');

        $latest = $thread->messages()
            ->with('user:id,name')
            ->latest('created_at')
            ->take(10)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'id'         => $thread->id,
            'title'      => $thread->title,
            'is_locked'  => (bool) $thread->is_locked,
            'created_at' => $thread->created_at?->toISOString(),
            'author'     => $thread->author ? [
                'id'   => $thread->author->id,
                'name' => $thread->author->name,
            ] : null,
            'latest_messages' => $latest->map(function (ChatMessage $m) {
                return [
                    'id'         => $m->id,
                    'user'       => $m->user ? [
                        'id'   => $m->user->id,
                        'name' => $m->user->name,
                    ] : null,
                    'body'       => $m->body,
                    'created_at' => $m->created_at?->toISOString(),
                ];
            }),
        ]);
    }

    public function messages(Request $r, ChatThread $thread)
    {
        $afterId = $r->integer('after_id');
        $limit   = (int) $r->query('limit', 50);
        $limit   = max(1, min($limit, 100));

        $q = $thread->messages()
            ->with('user:id,name');

        if ($afterId) {
            $q->where('id', '>', $afterId)
              ->orderBy('id', 'asc');
        } else {
            $q->orderBy('created_at', 'asc');
        }

        $messages = $q->take($limit)->get();

        return response()->json([
            'data' => $messages->map(function (ChatMessage $m) {
                return [
                    'id'         => $m->id,
                    'user'       => $m->user ? [
                        'id'   => $m->user->id,
                        'name' => $m->user->name,
                    ] : null,
                    'body'       => $m->body,
                    'created_at' => $m->created_at?->toISOString(),
                ];
            }),
        ]);
    }

    public function storeMessage(Request $r, ChatThread $thread)
    {
        abort_if($thread->is_locked, 403, 'Thread locked');

        $data = $r->validate([
            'body' => ['required', 'string', 'max:3000'],
        ]);

        $msg = $thread->messages()->create([
            'user_id' => Auth::id(),
            'body'    => $data['body'],
        ]);

        $msg->load('user:id,name');

        if ($msg->user_id) {
            DB::table('chat_thread_reads')->updateOrInsert(
                ['user_id' => $msg->user_id, 'chat_thread_id' => $thread->id],
                ['last_read_message_id' => $msg->id, 'last_read_at' => now(), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return response()->json([
            'id'         => $msg->id,
            'user'       => $msg->user ? [
                'id'   => $msg->user->id,
                'name' => $msg->user->name,
            ] : null,
            'body'       => $msg->body,
            'created_at' => $msg->created_at?->toISOString(),
        ], 201);
    }
}
