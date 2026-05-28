<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestionSource: string
{
    case AiGenerated = 'ai_generated';
    case UserCreated = 'user_created';
}
