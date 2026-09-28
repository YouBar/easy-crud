<?php

namespace Youbar\EasyCrud\Contracts;

use Illuminate\Database\Eloquent\Model;

interface AuthorizesActions
{
    /**
     * @param  Model|class-string<Model>  $target
     */
    public function authorize(string $ability, Model|string $target): void;
}
