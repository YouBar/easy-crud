<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Shapes;

use Illuminate\Database\Eloquent\Model;
use Youbar\EasyCrud\CrudController;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;
use Youbar\EasyCrud\Tests\Fixtures\Resources\Alternate\OffConventionResource;

/**
 * Mirrors PaymentController: a resource class that does not follow the
 * convention, plus a loadMorph()-style shaping step on show.
 */
class OffConventionResourceController extends CrudController
{
    protected string $model = Post::class;

    /** @var class-string|null */
    protected ?string $resource = OffConventionResource::class;

    protected function prepare(mixed $data, string $action): mixed
    {
        if ($data instanceof Model && $action === 'show') {
            // Stands in for $model->loadMorph('owner', [...]).
            $data->setAttribute('morphed', true);
        }

        return $data;
    }
}
