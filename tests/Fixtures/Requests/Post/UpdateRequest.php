<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Nested-by-model layout. Only Update exists here, so the resolver has to fall
 * through the flat pattern first and still find this one.
 */
class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['title' => 'sometimes|string|max:255'];
    }
}
