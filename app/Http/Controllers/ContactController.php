<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Download;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('contact');
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: hidden from people, filled in by bots.
        if (filled($request->input('website'))) {
            return redirect()->route('contact')->with('status', 'Thanks, your message was sent.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'subject' => ['nullable', 'string', 'max:190'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        ContactMessage::create($data + ['ip_hash' => Download::hashIp($request->ip())]);

        return redirect()->route('contact')->with('status', 'Thanks, your message was sent. We usually reply within two business days.');
    }
}
