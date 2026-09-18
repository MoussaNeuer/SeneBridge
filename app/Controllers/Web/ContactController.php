<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Models\ContactMessage;
use App\Support\App;
use App\Support\Audit;
use App\Support\Response;
use App\Validators\ContactValidator;

final class ContactController extends Controller
{
    public function show(): Response
    {
        return Response::view('public/contact', [
            'contactEmail' => config('mail.from.address', 'contact@senebridge.sn'),
            'contactPhone' => '+221 33 000 00 00',
        ]);
    }

    public function submit(): Response
    {
        $data = $this->request->only(['name', 'email', 'phone', 'subject', 'message']);
        $validation = ContactValidator::register($data);

        if (!$validation->passes()) {
            return $this->backWithErrors($validation->errors(), $data);
        }

        App::flash('success', 'Merci, votre message a bien été envoyé. Nous vous répondrons rapidement.');

        $message = ContactMessage::create([
            'name' => trim((string) $data['name']),
            'email' => mb_strtolower(trim((string) $data['email'])),
            'phone' => trim((string) ($data['phone'] ?? '')) !== '' ? trim((string) $data['phone']) : null,
            'subject' => trim((string) $data['subject']),
            'message' => trim((string) $data['message']),
            'is_read' => 0,
        ]);

        if ($message !== null) {
            Audit::log('contacts.message_received', 'contact_messages', (int) $message['id'], [], [
                'email' => $message['email'],
                'subject' => mb_substr($message['subject'], 0, 120),
            ]);
        }

        return Response::redirect(route('pages.contact'));
    }
}