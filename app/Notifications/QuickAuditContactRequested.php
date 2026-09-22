<?php

namespace App\Notifications;

use App\Models\Assessment;
use App\Services\QuickAuditScoringService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every real staff account (see recipients() below) the moment a
 * public /brzi-audit visitor asks to be contacted. Deliberately NOT sent on
 * every quiz completion — that would fire for every anonymous visitor who
 * never leaves contact info; the Filament "Leadovi" list is where those are
 * browsed at leisure. Sent synchronously (no ShouldQueue) since there's no
 * confirmed queue worker running yet — if that changes and this starts
 * slowing down the contact-form request, queue it then.
 */
class QuickAuditContactRequested extends Notification
{
    public function __construct(private readonly Assessment $assessment)
    {
        $this->assessment->loadMissing('company');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $company = $this->assessment->company;
        $score = app(QuickAuditScoringService::class)->overallScore($this->assessment)['score'];

        $message = (new MailMessage)
            ->subject("Novi lead: {$company->name}")
            ->greeting('Novi zahtjev za kontakt sa brzog audita')
            ->line("**Kompanija:** {$company->name}")
            ->when($company->website, fn (MailMessage $m) => $m->line("**Web:** {$company->website}"))
            ->line('**Ocjena brzog audita:** '.($score !== null ? number_format($score, 0).'/100' : 'nije izračunata'))
            ->line("**Kontakt:** {$company->contact_name} ({$company->contact_email})")
            ->when($company->contact_phone, fn (MailMessage $m) => $m->line("**Telefon:** {$company->contact_phone}"));

        if ($this->assessment->lead_message) {
            $message->line('**Poruka:**')->line($this->assessment->lead_message);
        }

        return $message->action('Pogledaj rezultat', route('quick-audit.results', $this->assessment));
    }
}
