<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Interview\GenerateReportAction;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInterviewReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public readonly InterviewSession $session,
        public readonly User $user,
    ) {}

    public function handle(GenerateReportAction $action): void
    {
        $action($this->user, $this->session);
    }
}
