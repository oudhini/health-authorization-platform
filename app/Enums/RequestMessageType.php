<?php

declare(strict_types=1);

namespace App\Enums;

enum RequestMessageType: string
{
    case InformationRequested = 'information_requested';
    case FacilityResponse = 'facility_response';
}
