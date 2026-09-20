<?php

namespace App\Http\Controllers\Chat;

use App\Events\RealtimeEvent;
use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\RouteAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Real-time resident <-> driver chat. One conversation per resident/driver
 * pair (see the unique constraint on chat_conversations), reused across
 * pickups. Both Resident and Driver route groups point at this same
 * controller — access is checked per-conversation (must be one of the two
 * participants) rather than by role, since a thread always has exactly one
 * of each.
 */
class ChatController extends Controller
{
    /**
     * List the authenticated user's conversations, newest activity first,
     * with the other participant's info and an unread count.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $column = $user->role === 'driver' ? 'driver_id' : 'resident_id';

        $conversations = ChatConversation::where($column, $user->id)
            ->with(['resident:id,name', 'driver:id,name'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ChatConversation $c) use ($user) {
                $other = $c->otherParticipantFor($user);
                $unread = ChatMessage::where('conversation_id', $c->id)
                    ->where('sender_id', '!=', $user->id)
                    ->where('is_read', false)
                    ->count();
                $lastMessage = ChatMessage::where('conversation_id', $c->id)
                    ->orderByDesc('created_at')
                    ->first(['body', 'created_at', 'sender_id']);

                return [
                    'id'               => $c->id,
                    'other_user_id'    => $other->id ?? null,
                    'other_user_name'  => $other->name ?? 'Unknown',
                    'last_message'     => $lastMessage->body ?? null,
                    'last_message_at'  => $c->last_message_at,
                    'unread_count'     => $unread,
                ];
            });

        return response()->json($conversations);
    }

    /**
     * Fetch (paginated, oldest-first for display) messages in a
     * conversation the caller is a participant of.
     */
    public function messages(Request $request, $conversationId)
    {
        $conversation = ChatConversation::find($conversationId);

        if (!$conversation || !$conversation->includesUser($request->user())) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        $limit = min((int) $request->query('limit', 50), 200);

        $messages = ChatMessage::where('conversation_id', $conversation->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'conversation' => [
                'id'              => $conversation->id,
                'other_user_id'   => optional($conversation->otherParticipantFor($request->user()))->id,
                'other_user_name' => optional($conversation->otherParticipantFor($request->user()))->name,
            ],
            'messages' => $messages,
        ]);
    }

    public function send(Request $request, $conversationId)
    {
        $conversation = ChatConversation::find($conversationId);

        if (!$conversation || !$conversation->includesUser($request->user())) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Message cannot be empty'], 400);
        }

        $user = $request->user();

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'sender_role'     => $user->role,
            'body'            => trim($request->body),
            'created_at'      => now(),
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        $payload = [
            'id'              => $message->id,
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'sender_role'     => $user->role,
            'sender_name'     => $user->name,
            'body'            => $message->body,
            'created_at'      => $message->created_at,
        ];

        // Anyone with the thread open gets it instantly...
        broadcast(new RealtimeEvent("conversation.{$conversation->id}", 'chat-message', $payload));

        // ...and the recipient gets a ping on their personal channel too,
        // so a badge/toast can appear even if they're not viewing the
        // thread right now.
        $recipientId = $user->id === $conversation->resident_id
            ? $conversation->driver_id
            : $conversation->resident_id;
        $recipientChannel = $user->id === $conversation->resident_id
            ? "driver.{$recipientId}"
            : "resident.{$recipientId}";

        broadcast(new RealtimeEvent($recipientChannel, 'chat-message', $payload));

        return response()->json(['message' => 'Sent', 'data' => $payload], 201);
    }

    public function markRead(Request $request, $conversationId)
    {
        $conversation = ChatConversation::find($conversationId);

        if (!$conversation || !$conversation->includesUser($request->user())) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        ChatMessage::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Marked as read']);
    }

    /**
     * Resident-only convenience: find or create the thread with today's
     * assigned driver, so the resident dashboard can just call this and
     * get a conversation id without knowing the driver's id up front.
     */
    public function startWithTodaysDriver(Request $request)
    {
        $resident = $request->user();
        $today = Carbon::now('Asia/Manila')->toDateString();

        $assignment = RouteAssignment::where('assigned_date', $today)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderByRaw("FIELD(status, 'in_progress', 'pending')")
            ->first();

        if (!$assignment) {
            return response()->json(['error' => 'No driver assigned for today yet'], 404);
        }

        $driver = User::find($assignment->driver_id);
        if (!$driver) {
            return response()->json(['error' => 'Driver not found'], 404);
        }

        $conversation = ChatConversation::firstOrCreate(
            ['resident_id' => $resident->id, 'driver_id' => $driver->id],
            ['route_assignment_id' => $assignment->id]
        );

        // Keep pointing at the latest assignment even on an existing thread.
        if ($conversation->route_assignment_id !== $assignment->id) {
            $conversation->update(['route_assignment_id' => $assignment->id]);
        }

        return response()->json([
            'conversation_id' => $conversation->id,
            'driver_id'       => $driver->id,
            'driver_name'     => $driver->name,
        ]);
    }
}
