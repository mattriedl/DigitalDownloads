<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Members' support tickets: open one, follow the conversation, reply, close. */
class TicketController extends Controller
{
    private const ATTACHMENT_RULES = ['nullable', 'array', 'max:3'];

    private const FILE_RULES = ['file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf,txt,log,zip'];

    public function index(Request $request): View
    {
        return view('account.tickets.index', [
            'tickets' => $request->user()->tickets()->latest('last_reply_at')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('account.tickets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'priority' => ['required', 'in:low,normal,high'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'attachments' => self::ATTACHMENT_RULES,
            'attachments.*' => self::FILE_RULES,
        ]);

        $ticket = DB::transaction(function () use ($request, $data) {
            $ticket = $request->user()->tickets()->create([
                'subject' => $data['subject'],
                'priority' => $data['priority'],
                'status' => 'open',
                'last_reply_at' => now(),
            ]);
            $this->addMessage($ticket, $request, $data['message']);

            return $ticket;
        });

        Notifier::admin('admin_new_ticket', [
            'name' => $request->user()->name,
            'subject' => $ticket->subject,
            'message' => $data['message'],
            'ticket_url' => url('/admin/support-tickets/'.$ticket->id),
        ]);

        return redirect()->route('tickets.show', $ticket)->with('status', 'Ticket opened. We\'ll reply here and by email.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $this->authorizeOwner($request, $ticket);

        return view('account.tickets.show', [
            'ticket' => $ticket->load(['messages.user:id,name', 'messages.attachments']),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeOwner($request, $ticket);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'attachments' => self::ATTACHMENT_RULES,
            'attachments.*' => self::FILE_RULES,
        ]);

        DB::transaction(function () use ($ticket, $request, $data) {
            $this->addMessage($ticket, $request, $data['message']);
            // Replying reopens a closed ticket.
            $ticket->update(['status' => 'customer_reply', 'last_reply_at' => now()]);
        });

        Notifier::admin('admin_new_ticket', [
            'name' => $request->user()->name,
            'subject' => 'Reply: '.$ticket->subject,
            'message' => $data['message'],
            'ticket_url' => url('/admin/support-tickets/'.$ticket->id),
        ]);

        return redirect()->route('tickets.show', $ticket)->with('status', 'Reply sent.');
    }

    public function close(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeOwner($request, $ticket);
        $ticket->update(['status' => 'closed']);

        return redirect()->route('tickets.show', $ticket)->with('status', 'Ticket closed. Reply any time to reopen it.');
    }

    /** Attachments are private files; only the ticket owner and staff can open them. */
    public function attachment(Request $request, SupportTicket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        $this->authorizeOwner($request, $ticket);
        abort_unless($attachment->message?->support_ticket_id === $ticket->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    private function addMessage(SupportTicket $ticket, Request $request, string $body): TicketMessage
    {
        $message = $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'is_staff' => false,
            'body' => $body,
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $message->attachments()->create([
                'path' => $file->store('ticket-attachments', 'local'),
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 190),
                'size' => $file->getSize(),
            ]);
        }

        return $message;
    }

    private function authorizeOwner(Request $request, SupportTicket $ticket): void
    {
        $user = $request->user();
        abort_unless($ticket->user_id === $user->id || $user->isStaff(), 404);
    }
}
