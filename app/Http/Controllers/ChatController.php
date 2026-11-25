<?php

namespace App\Http\Controllers;

use App\Services\Minecraft\Economy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Live chat for verified players only. The page polls for new messages; a
 * message is appended with the sender's verified IGN, never a typed name.
 */
class ChatController extends Controller
{
    private const PAGE = 100;

    public function __construct(private readonly Economy $economy) {}

    public function index()
    {
        $user = Auth::user();

        // Reading is open to everyone; posting is not.
        return view('chat', [
            'ign' => $user !== null ? $user->minecraft_ign : null,
            'can_write' => $user !== null && $this->economy->verified($user),
        ]);
    }

    public function messages(Request $r): JsonResponse
    {
        $after = (int) $r->query('after', 0);

        $rows = $after > 0
            ? DB::table('mc_chat_messages')->where('id', '>', $after)->orderBy('id')->limit(self::PAGE)->get()
            : DB::table('mc_chat_messages')->orderByDesc('id')->limit(60)->get()->reverse()->values();

        $messages = [];
        $latest = $after;

        foreach ($rows as $row) {
            $latest = max($latest, (int) $row->id);

            $messages[] = [
                'id' => (int) $row->id,
                'ign' => (string) $row->ign,
                'body' => (string) $row->body,
                'at' => substr((string) $row->created_at, 11, 5),
            ];
        }

        return response()->json([
            'ok' => true,
            'messages' => $messages,
            'latest' => $latest,
        ]);
    }

    public function send(Request $r): JsonResponse
    {
        $user = Auth::user();

        if (!$this->economy->verified($user)) {
            return response()->json(['ok' => false, 'error' => 'Verify your Minecraft name first.'], 403);
        }

        $data = $r->validate([
            'body' => ['required', 'string', 'max:200'],
        ]);

        $text = trim((string) preg_replace('/\s+/u', ' ', $data['body']));

        if ($text === '') {
            return response()->json(['ok' => false, 'error' => 'Write something first.'], 422);
        }

        $last = DB::table('mc_chat_messages')->where('user_id', $user->id)->orderByDesc('id')->first();

        if ($last !== null && strtotime((string) $last->created_at) > time() - 2) {
            return response()->json(['ok' => false, 'error' => 'Slow down a moment.'], 429);
        }

        $id = DB::table('mc_chat_messages')->insertGetId([
            'user_id' => $user->id,
            'ign' => (string) $user->minecraft_ign,
            'body' => mb_substr($text, 0, 200),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => [
                'id' => (int) $id,
                'ign' => (string) $user->minecraft_ign,
                'body' => mb_substr($text, 0, 200),
                'at' => now()->format('H:i'),
            ],
        ]);
    }
}
