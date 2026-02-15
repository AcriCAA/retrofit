<?php

namespace App\Jobs;

use App\Models\SearchRequest;
use App\Models\SearchResult;
use App\Notifications\NewSearchResultsNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyUserOfResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SearchRequest $searchRequest,
        public array $newResults
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $user = $this->searchRequest->user;
        $preferences = $user->notificationPreference;

        if (! $preferences) {
            // Default: send notifications
            $user->notify(new NewSearchResultsNotification($this->searchRequest, $this->newResults));
            $this->markNotified();

            return;
        }

        if (! $preferences->email_enabled || ! $preferences->notify_new_results) {
            return;
        }

        if ($preferences->email_frequency === 'instant') {
            $user->notify(new NewSearchResultsNotification($this->searchRequest, $this->newResults));
            $this->markNotified();
        }
        // daily/weekly digests would be handled by a separate scheduled job
    }

    protected function markNotified(): void
    {
        $ids = collect($this->newResults)->pluck('id')->filter();

        if ($ids->isNotEmpty()) {
            SearchResult::whereIn('id', $ids)->update(['is_notified' => true]);
        }
    }
}
