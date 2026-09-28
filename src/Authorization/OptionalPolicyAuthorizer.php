<?php

namespace Youbar\EasyCrud\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Youbar\EasyCrud\Contracts\AuthorizesActions;

class OptionalPolicyAuthorizer implements AuthorizesActions
{
    public function __construct(protected Gate $gate) {}

    public function authorize(string $ability, Model|string $target): void
    {
        $policy = $this->gate->getPolicyFor($target);

        if ($policy === null || ! method_exists($policy, $ability)) {
            return;
        }

        $this->gate->authorize($ability, $target);
    }
}
