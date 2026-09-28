<?php

namespace Youbar\EasyCrud;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Controller;
use Youbar\EasyCrud\Concerns\HandlesCrud;

abstract class CrudController extends Controller
{
    use HandlesCrud;

    /**
     * The Eloquent model this controller exposes. The only required declaration.
     *
     * @var class-string<Model>
     */
    protected string $model;

    /**
     * The single-item resource class. Null falls through to the conventions table.
     *
     * @var class-string|null
     */
    protected ?string $resource = null;

    /**
     * The collection resource class. Null falls through to the conventions table.
     *
     * @var class-string|null
     */
    protected ?string $collection = null;

    /**
     * Explicit request classes, keyed by action. Beats both rules() and conventions.
     *
     * @var array<string, class-string<FormRequest>>
     */
    protected array $requests = [];
}
