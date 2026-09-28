<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conventions
    |--------------------------------------------------------------------------
    |
    | ROLE => how to find that role's class for a model. Ships empty: nothing is
    | looked up until you add a row. Patterns take {Model}, {Models}, {model},
    | {models}, {Action}, {action}, {Namespace}, {SubNamespace}, {Domain} and
    | {FQCN}, and a value may be one pattern, an ordered list, a closure, or
    | null to disable.
    |
    | The package acts on 'resource', 'collection' and 'request'. Any other row
    | resolves a class and waits for you to read it with $this->resolved().
    |
    | php artisan easy-crud:conventions {Model} shows what resolves.
    |
    */

    'conventions' => [

        // 'resource'   => ['App\Http\Resources\{Model}Resource'],
        // 'collection' => ['App\Http\Resources\{Model}Collection'],
        // 'request'    => ['App\Http\Requests\{Action}{Model}Request'],

    ],

    /*
    |--------------------------------------------------------------------------
    | Model namespace
    |--------------------------------------------------------------------------
    |
    | Where your models start. {SubNamespace} is a model's position below it, so
    | App\Models\Billing\Payment gives "Billing" and App\Models\Post gives "".
    | Only {SubNamespace} reads this.
    |
    */

    'model_namespace' => null,

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'strategy' => 'length_aware',   // length_aware | simple | cursor | none
        'default' => 15,
        'max' => 100,
        'query_keys' => ['perPage', 'per_page'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | With no request class and no rules(), an action runs unvalidated and the
    | raw input reaches the model. Set this to true to throw instead whenever
    | store or update has nothing to validate with.
    |
    */

    'strict_requests' => false,

    /*
    |--------------------------------------------------------------------------
    | Responses
    |--------------------------------------------------------------------------
    */

    'responses' => [
        'store' => 201,
        'update' => 200,
        'destroy' => 204,
        'destroy_returns_resource' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | optional: enforce an ability the policy defines, skip one it omits
    | strict:   a missing policy or ability is an error
    | none:     never authorize
    |
    */

    'authorization' => 'optional',

];
