<?php

namespace Youbar\EasyCrud\Authorization;

use Illuminate\Database\Eloquent\Model;
use Youbar\EasyCrud\Contracts\AuthorizesActions;

/**
 * Never authorizes anything. For applications that gate entirely in middleware.
 */
class NullAuthorizer implements AuthorizesActions
{
    public function authorize(string $ability, Model|string $target): void
    {
        //
    }
}
