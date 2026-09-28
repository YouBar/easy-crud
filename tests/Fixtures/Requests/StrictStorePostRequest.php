<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StrictStorePostRequest extends FormRequest
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
        return ['title' => 'required|string|starts_with:OK'];
    }
}
