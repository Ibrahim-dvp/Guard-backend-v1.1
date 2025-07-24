<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadStatus: string
{
    case NEW = 'new';
    case ASSIGNED_TO_MANAGER = 'assigned_to_manager';
    case DECLINED_BY_MANAGER = 'declined_by_manager';
    case ASSIGNED_TO_AGENT = 'assigned_to_agent';
    case DECLINED_BY_AGENT = 'declined_by_agent';
    case ACCEPTED = 'accepted';
    case CONTACTED = 'contacted';
    case QUALIFIED = 'qualified';
    case CONVERTED = 'converted';
    case REJECTED = 'rejected';
}
