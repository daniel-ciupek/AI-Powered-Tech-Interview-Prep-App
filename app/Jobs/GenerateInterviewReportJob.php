<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Interview\GenerateReportAction;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GenerateInterviewReportJob
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(
        public readonly InterviewSession $session,
        public readonly User $user,
    ) {}

    public function handle(GenerateReportAction $action): void
    {
        $action($this->user, $this->session);
    }
}
