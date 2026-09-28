<?php

namespace Youbar\EasyCrud\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Youbar\EasyCrud\Contracts\AuthorizesActions;
use Youbar\EasyCrud\Exceptions\CrudConfigurationException;

class StrictPolicyAuthorizer implements AuthorizesActions
{
    public function __construct(protected Gate $gate) {}

    public function authorize(string $ability, Model|string $target): void
    {
        $policy = $this->gate->getPolicyFor($target);
        $class = $target instanceof Model ? $target::class : $target;

        if ($policy === null) {
            throw new CrudConfigurationException(sprintf(
                'No policy registered for [%s] and easy-crud.authorization is "strict". '
                .'Register a policy, or switch to "optional" to skip authorization when none exists.',
                $class,
            ));
        }

        if (! method_exists($policy, $ability)) {
            throw new CrudConfigurationException(sprintf(
                'Policy [%s] does not define the [%s] ability required by easy-crud in "strict" mode.',
                $policy::class,
                $ability,
            ));
        }

        $this->gate->authorize($ability, $target);
    }
}
