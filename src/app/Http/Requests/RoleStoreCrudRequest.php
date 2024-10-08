<?php

namespace Backpack\PermissionManager\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleStoreCrudRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return hncore_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255|unique:'.config('permission.table_names.roles', 'roles').',name',
        ];

        return $rules;
    }
}
