<?php

namespace App\Jobs;

use App\Models\ExpoPushToken;
use App\Models\User;
use App\Services\NotificationPreferenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendExpoPushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public string $section = '';

    /** @param array<string, mixed> $target */
    public function __construct(
        public int $userId,
        public string $title,
        public string $body,
        public array $target,
        string $section = '',
    ) {
        $this->section = $section;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationPreferenceService $preferences): void
    {
        $user = User::query()->find($this->userId);

        if (! $user || ! $user->is_active || ! in_array('broadcast', $preferences->channels($user, $this->section), true)) {
            return;
        }

        $tokens = ExpoPushToken::query()->where('user_id', $this->userId)->get();

        if ($tokens->isEmpty()) {
            return;
        }

        $messages = $tokens->map(fn (ExpoPushToken $token): array => [
            'to' => $token->token,
            'title' => $this->title,
            'body' => $this->body,
            'sound' => 'default',
            'data' => ['target' => $this->target],
        ])->values()->all();

        $request = Http::acceptJson()->timeout(10)->retry(2, 250);
        if ($accessToken = config('services.expo.access_token')) {
            $request = $request->withToken($accessToken);
        }

        $response = $request->post(config('services.expo.push_url'), $messages);
        $response->throw();

        collect($response->json('data', []))->each(function (array $ticket, int $index) use ($tokens): void {
            if (data_get($ticket, 'details.error') === 'DeviceNotRegistered') {
                $tokens->get($index)?->delete();
            }
        });
    }
}
