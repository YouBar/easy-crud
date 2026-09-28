<?php

namespace Youbar\EasyCrud\Routing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Controller;
use Youbar\EasyCrud\Concerns\HandlesCrud;

class ConventionController extends Controller
{
    use HandlesCrud;

    /**
     * @var class-string<Model>
     */
    protected string $model;

    /**
     * @param  class-string<Model>  $model
     */
    public function __construct(string $model)
    {
        $this->model = $model;
    }
}
