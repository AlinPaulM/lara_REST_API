<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResizeImageRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = [
            'image' => ['required'],
            'w' => ['required', 'regex:/^\d+(\.\d+)?%?$/'], // e.g. 50, 50%, 50.123, 50.123%
            'h' => 'regex:/^\d+(\.\d+)?%?$/',
            'album_id' => 'exists:\App\Models\Album,id'
        ];

        $image = $this->all()['image'] ?? false;
        if($image && $image instanceof \Illuminate\Http\UploadedFile) {
            $rules['image'][] = 'image'; // https://laravel.com/docs/9.x/validation#rule-image
        } else {
            $rules['image'][] = 'url'; // https://laravel.com/docs/9.x/validation#rule-url
        }

        return $rules;
    }
}
