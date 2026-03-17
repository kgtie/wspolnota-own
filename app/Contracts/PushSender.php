<?php

namespace App\Contracts;

use App\Support\Push\PushMessage;
use App\Support\Push\PushSendResult;

interface PushSender
{
    public function send(PushMessage $message, bool $validateOnly = false): PushSendResult;
}
