<?php

namespace App\Events;

use App\Models\AnnouncementSet;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementPackagePublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly AnnouncementSet $announcementSet) {}
}
