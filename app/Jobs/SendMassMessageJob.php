<?php

namespace App\Jobs;

use App\Mail\MassMessageMail;
use App\Models\Mass;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMassMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $massId,
        public string $subjectLine,
        public string $bodyText,
    ) {}

    public function handle(): void
    {
        $mass = Mass::query()->findOrFail($this->massId);

        $mass->attendees()
            ->select('email')
            ->whereNotNull('email')
            ->distinct()
            ->orderBy('email')
            ->chunk(500, function ($rows) use ($mass) {
                foreach ($rows as $row) {
                    Mail::to($row->email)->queue(
                        new MassMessageMail($mass, $this->subjectLine, $this->bodyText)
                    );
                }
            });
    }
}
