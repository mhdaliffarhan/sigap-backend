<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Notification;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Asset;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $notification;
    public $actionUrl;
    public $ticket;
    public $asset;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Notification $notification)
    {
        $this->user = $user;
        $this->notification = $notification;
        
        // Load ticket data if available
        if ($notification->reference_type === 'ticket' && $notification->reference_id) {
            $this->ticket = Ticket::with(['user', 'category', 'assignedUser'])->find($notification->reference_id);
            
            // Load asset data if ticket has kode_barang and nup
            if ($this->ticket && $this->ticket->kode_barang && $this->ticket->nup) {
                $this->asset = Asset::where('kode_barang', $this->ticket->kode_barang)
                    ->where('nup', $this->ticket->nup)
                    ->first();
            }
        }
        
        // Build action URL
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        
        if ($notification->reference_type === 'ticket' && $notification->reference_id) {
            $this->actionUrl = $frontendUrl . '/tickets/' . $notification->reference_id;
        } elseif ($notification->action_url) {
            $this->actionUrl = $frontendUrl . $notification->action_url;
        } else {
            $this->actionUrl = $frontendUrl . '/notifications';
        }
    }

    /**
     * Send email using Mailtrap API or SMTP based on config
     */
    public function send($mailer)
    {
        $mailDriver = env('MAIL_DRIVER', 'smtp');
        
        // Use Mailtrap API if driver is 'mailtrap'
        if ($mailDriver === 'mailtrap') {
            $apiToken = env('MAILTRAP_API_TOKEN');
            
            if (!$apiToken) {
                throw new \Exception('MAILTRAP_API_TOKEN not configured');
            }

            // Render email body
            $body = view('emails.notification', [
                'user' => $this->user,
                'notification' => $this->notification,
                'actionUrl' => $this->actionUrl,
                'ticket' => $this->ticket,
                'asset' => $this->asset,
            ])->render();

            // Create Mailtrap email
            $email = (new MailtrapEmail())
                ->from(new Address(
                    env('MAIL_FROM_ADDRESS', 'hello@sigapti.azify.page'),
                    env('MAIL_FROM_NAME', 'SIGAP-TI BPS NTB')
                ))
                ->to(new Address($this->user->email, $this->user->name))
                ->subject($this->notification->title . ' - SIGAP-TI BPS NTB')
                ->html($body)
                ->category('Notification');

            // Send via Mailtrap API
            $client = MailtrapClient::initSendingEmails(apiKey: $apiToken);
            return $client->send($email);
        }
        
        // Use default Laravel SMTP (Gmail or other SMTP)
        return parent::send($mailer);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->notification->title . ' - SIGAP-TI BPS NTB',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
