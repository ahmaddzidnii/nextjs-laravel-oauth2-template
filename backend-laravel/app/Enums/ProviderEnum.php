<?php

namespace App\Enums;

enum ProviderEnum: string
{
    case GOOGLE = 'google';
    case GITHUB = 'github';

    /**
     * Add more provider here
     */
}
