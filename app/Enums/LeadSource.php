<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadSource: string
{
    case WEB = 'web';
    case REFERRAL = 'referral';
    case COLD_CALL = 'cold_call';
    case EVENT = 'event';
    case OTHER = 'other';
}
