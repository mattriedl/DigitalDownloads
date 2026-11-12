<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Download;
use App\Support\Captcha;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
            ...Captcha::rules(),
        ]);
        $data = Arr::only($data, ['name', 'email', 'subject', 'message']);

        ContactMessage::create($data + ['ip_hash' => Download::hashIp($request->ip())]);

        Notifier::admin('admin_contact_message', ['subject' => ($data['subject'] ?? null) ?: '(no subject)'] + $data);

        return redirect()->route('contact')->with('status', 'Thanks, your message was sent. We usually reply within two business days.');
    }
}
