<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Shapes;

use Illuminate\Database\Eloquent\Model;
use Youbar\EasyCrud\CrudController;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;

class ValidatedDestroyController extends CrudController
{
    protected string $model = Post::class;

    /**
     * @return array<string, mixed>
     */
    protected function rules(string $action): array
    {
        return $action === 'destroy' ? ['reason' => 'required|string'] : [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function delete(Model $model, array $data = []): void
    {
        $model->forceFill(['title' => 'deleted: '.($data['reason'] ?? '?')])->save();

        parent::delete($model, $data);
    }
}
