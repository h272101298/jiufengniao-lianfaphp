<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductCategoryPost extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
            'type_id'=>'required',
            'categories'=>'required|array',
            'category.*'=>'required|array',
            'category.*.title'=>'required',
            'category.*.detail'=>'required|array',
            'category.*.detail.content'=>'required'
        ];
    }
}
