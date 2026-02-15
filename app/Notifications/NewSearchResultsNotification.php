<?php

namespace App\Notifications;

use App\Models\SearchRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSearchResultsNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected SearchRequest $searchRequest,
        protected array $newResults
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->newResults);
        $bestMatch = $this->newResults[0] ?? null;

        $mail = (new MailMessage)
            ->subject("RetroFit: {$count} new " . ($count === 1 ? 'match' : 'matches') . " for \"{$this->searchRequest->title}\"")
            ->greeting("New matches found!")
            ->line("We found {$count} new " . ($count === 1 ? 'listing' : 'listings') . " matching your search for **{$this->searchRequest->title}**.");

        if ($bestMatch && isset($bestMatch['title'])) {
            $price = isset($bestMatch['price']) ? '$' . number_format($bestMatch['price'], 2) : 'Price not listed';
            $mail->line("**Best match:** {$bestMatch['title']} — {$price}");
        }

        $mail->action('View Results', url("/searches/{$this->searchRequest->id}"))
            ->line('Happy hunting!');

        return $mail;
    }
}
