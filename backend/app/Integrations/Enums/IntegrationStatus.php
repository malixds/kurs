<?php

namespace App\Integrations\Enums;

enum IntegrationStatus: string
{
    case Disconnected = 'disconnected';
    case Connected = 'connected';
    case Error = 'error';
}
