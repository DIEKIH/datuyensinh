<?php

namespace App\Http\Controllers;

use App\Models\AdviseMessage;
use App\Models\AdviseSession;
use App\Models\AdviseTicket;
use Illuminate\Http\Request;

class AdviseTicketController extends Controller
{
    public function check(Request $request)
    {
        $data = $request->validate([
            'conversation_id' => 'required|string|max:128',
        ]);

        $session = AdviseSession::where(
            'thread_id',
            $data['conversation_id']
        )->first();

        if (!$session) {
            return response()->json([
                'success' => true,
                'has_answer' => false,
            ]);
        }

        $ticket = AdviseTicket::where('session_id', $session->id)
            ->where('status', 'answered')
            ->whereNotNull('staff_answer')
            ->whereNull('delivered_at')
            ->orderBy('answered_at', 'asc')
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => true,
                'has_answer' => false,
            ]);
        }

        $ticket->update([
            'delivered_at' => now(),
        ]);

        AdviseMessage::create([
            'session_id'  => $session->id,
            'reply_to_id' => $ticket->user_message_id,
            'role'        => 'assistant',
            'content'     => $ticket->staff_answer,
            'input_type'  => 'text',
            'sent_at'     => now(),
        ]);

        return response()->json([
            'success' => true,
            'has_answer' => true,
            'ticket' => [
                'ticket_code' => $ticket->ticket_code,
                'answer'      => $ticket->staff_answer,
                'answered_at' => $ticket->answered_at
                    ? date(
                        'd/m/Y H:i',
                        strtotime($ticket->answered_at)
                    )
                    : null,
            ],
        ]);
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'ticket_code' => 'required|string|max:50',
        ]);

        $code = strtoupper(trim($data['ticket_code']));

        $ticket = AdviseTicket::where(
            'ticket_code',
            $code
        )->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy mã tra cứu.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'ticket' => [
                'ticket_code' => $ticket->ticket_code,
                'question'    => $ticket->question,
                'status'      => $ticket->status,
                'answer'      => $ticket->staff_answer,
                'answered_at' => $ticket->answered_at
                    ? date(
                        'd/m/Y H:i',
                        strtotime($ticket->answered_at)
                    )
                    : null,
                'created_at' => $ticket->created_at
                    ? date(
                        'd/m/Y H:i',
                        strtotime($ticket->created_at)
                    )
                    : null,
            ],
        ]);
    }
}
