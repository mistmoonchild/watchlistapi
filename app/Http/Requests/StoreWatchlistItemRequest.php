<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWatchlistItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'omdb_id' => 'required|string',
            'status' => 'nullable|in:to_watch,watched',
            'rating' => 'nullable|integer|min:1|max:5',
            'personal_notes' => 'nullable|string|max:1000'
        ];
    }
}
