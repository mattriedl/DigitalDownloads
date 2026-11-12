<?php

namespace App\Mail;

use App\Models\NotificationTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** An email built from an admin-editable template (Admin → System → Email templates). */
class TemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $renderedSubject;

    public string $renderedBody;

    public function __construct(NotificationTemplate $template, array $vars)
    {
        $vars += [
            'site_name' => setting('site_name', config('app.name')),
            'site_url' => setting('site_url', config('app.url')),
        ];

        $this->renderedSubject = NotificationTemplate::render($template->subject, $vars);
        $this->renderedBody = NotificationTemplate::render($template->email_body, $vars);
    }

    public function envelope(): Envelope
    {
        $address = setting('mail_from_address') ?: config('mail.from.address');
        $name = setting('mail_from_name') ?: setting('site_name', config('mail.from.name'));

        return new Envelope(
            from: new Address($address, $name),
            subject: $this->renderedSubject,
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.template', with: ['body' => $this->renderedBody]);
    }
}
