<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Repetition;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuestionReviewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Repetition $repetition) {}
}
