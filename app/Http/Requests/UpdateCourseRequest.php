<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'instructor_id' => ['sometimes', 'integer', 'exists:instructors,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'min:20'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'level' => ['sometimes', 'string', 'in:beginner,intermediate,advanced'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
