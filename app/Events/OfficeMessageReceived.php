<?php

namespace App\Events;

use App\Models\OfficeMessage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfficeMessageReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly OfficeMessage $message) {}
}
