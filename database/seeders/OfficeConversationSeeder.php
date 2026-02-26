<?php

namespace Database\Seeders;

use App\Models\OfficeConversation;
use App\Models\OfficeMessage;
use App\Models\Parish;
use App\Models\User;
use Illuminate\Database\Seeder;

class OfficeConversationSeeder extends Seeder
{
    public function run(): void
    {
        $parishes = Parish::query()->get();

        if ($parishes->isEmpty()) {
            $this->command?->warn('Brak parafii. Pominieto seedowanie kancelarii online.');

            return;
        }

        $conversationCount = 0;
        $messageCount = 0;
        $attachmentCount = 0;

        foreach ($parishes as $parish) {
            $priest = User::query()
                ->where('role', '>=', 1)
                ->whereHas('managedParishes', fn ($query) => $query
                    ->where('parish_id', $parish->id)
                    ->where('parish_user.is_active', true))
                ->orderBy('id')
                ->first();

            if (! $priest) {
                continue;
            }

            $eligibleUsers = User::query()
                ->where('role', 0)
                ->whereNotNull('email_verified_at')
                ->where('is_user_verified', true)
                ->whereKeyNot($priest->getKey())
                ->inRandomOrder()
                ->limit(8)
                ->get();

            if ($eligibleUsers->isEmpty()) {
                continue;
            }

            $targetConversations = min(random_int(2, 4), $eligibleUsers->count());

            for ($i = 0; $i < $targetConversations; $i++) {
                $parishioner = $eligibleUsers[$i];
                $startedAt = now()->subDays(random_int(2, 75))->subHours(random_int(1, 18));
                $isClosed = random_int(1, 100) <= 35;

                $conversation = new OfficeConversation([
                    'parish_id' => $parish->id,
                    'parishioner_user_id' => $parishioner->id,
                    'priest_user_id' => $priest->id,
                    'status' => $isClosed ? OfficeConversation::STATUS_CLOSED : OfficeConversation::STATUS_OPEN,
                ]);
                $conversation->created_at = $startedAt;
                $conversation->updated_at = $startedAt;
                $conversation->save();

                $messagesInConversation = random_int(3, 7);
                $messageTime = $startedAt->copy();
                $lastMessageAt = $startedAt->copy();

                for ($messageIndex = 0; $messageIndex < $messagesInConversation; $messageIndex++) {
                    $senderId = match (true) {
                        $messageIndex === 0 => $parishioner->id,
                        $messageIndex % 2 === 0 => $parishioner->id,
                        default => $priest->id,
                    };

                    $body = $senderId === $parishioner->id
                        ? $this->parishionerMessageBody($messageIndex)
                        : $this->priestMessageBody($messageIndex);

                    $message = new OfficeMessage([
                        'office_conversation_id' => $conversation->id,
                        'sender_user_id' => $senderId,
                        'body' => $body,
                        'has_attachments' => false,
                        'read_by_parishioner_at' => $senderId === $priest->id ? $messageTime->copy()->addMinutes(random_int(10, 180)) : $messageTime->copy(),
                        'read_by_priest_at' => $senderId === $parishioner->id ? $messageTime->copy()->addMinutes(random_int(10, 180)) : $messageTime->copy(),
                    ]);

                    $message->created_at = $messageTime;
                    $message->updated_at = $messageTime;
                    $message->save();
                    $lastMessageAt = $messageTime->copy();

                    $messageCount++;

                    if (random_int(1, 100) <= 28) {
                        $message
                            ->addMediaFromString($this->buildAttachmentContent($conversation, $message))
                            ->usingFileName(sprintf('kancelaria-%d-%d.txt', $conversation->id, $message->id))
                            ->withCustomProperties([
                                'visibility' => 'private',
                                'parish_id' => $conversation->parish_id,
                                'conversation_id' => $conversation->id,
                                'sender_user_id' => $senderId,
                            ])
                            ->toMediaCollection('attachments', 'office');

                        $message->update(['has_attachments' => true]);
                        $attachmentCount++;
                    }

                    $messageTime->addMinutes(random_int(8, 220));
                }

                $conversation->update([
                    'last_message_at' => $lastMessageAt,
                    'closed_at' => $isClosed ? $lastMessageAt->copy()->addMinutes(random_int(30, 480)) : null,
                ]);

                $conversationCount++;
            }
        }

        $this->command?->info('');
        $this->command?->info('💬 Seeder kancelarii online zakonczony.');
        $this->command?->table(
            ['Metryka', 'Wartosc'],
            [
                ['Konwersacje', (string) $conversationCount],
                ['Wiadomosci', (string) $messageCount],
                ['Zalaczniki', (string) $attachmentCount],
            ],
        );
    }

    protected function parishionerMessageBody(int $index): string
    {
        $messages = [
            'Szczesc Boze, prosze o informacje jakie dokumenty sa potrzebne do chrztu.',
            'Czy moge prosic o potwierdzenie terminu spotkania w kancelarii?',
            'Przesylam brakujace dane i prosze o dalsze wskazowki.',
            'Dziekuje za odpowiedz, czekam na dalsza informacje.',
            'Czy jest mozliwosc zalatwienia tej sprawy w przyszlym tygodniu?',
        ];

        return $messages[$index % count($messages)];
    }

    protected function priestMessageBody(int $index): string
    {
        $messages = [
            'Szczesc Boze, dziekuje za wiadomosc. Prosze przeslac dokumenty, ktore sa wskazane na liscie.',
            'Potwierdzam, otrzymalem zgloszenie. Prosze o uzupelnienie jednego zalacznika.',
            'Termin jest mozliwy. Prosze przyjsc z dowodem osobistym i odpisem aktu.',
            'Dziekuje, dokumenty sa kompletne. W razie pytan prosze odpisac w tej rozmowie.',
            'Sprawe mozemy domknac po potwierdzeniu ostatniego dokumentu.',
        ];

        return $messages[$index % count($messages)];
    }

    protected function buildAttachmentContent(OfficeConversation $conversation, OfficeMessage $message): string
    {
        return implode(PHP_EOL, [
            'Wspolnota - Kancelaria online (zalacznik testowy)',
            'Parafia ID: '.$conversation->parish_id,
            'Konwersacja ID: '.$conversation->id,
            'Wiadomosc ID: '.$message->id,
            'Data wygenerowania: '.now()->format('Y-m-d H:i:s'),
        ]);
    }
}
