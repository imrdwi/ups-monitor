<?php

namespace App\Http\Requests;

use App\Models\UpsUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsUnitRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $unit = $this->route('unit');

        return [
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'name' => ['required', 'string', 'max:100'],
            'serial_number' => ['required', 'string', 'max:100', Rule::unique('ups_units')->ignore($unit instanceof UpsUnit ? $unit->id : null)],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'rated_power_va' => ['required', 'integer', 'min:100', 'max:10000000'],
            'phases' => ['required', Rule::in([1, 3])],
            'is_modular' => ['boolean'],
            'battery_brand' => ['nullable', 'string', 'max:100'],
            'battery_type' => ['nullable', 'string', 'max:100'],
            'battery_capacity' => ['nullable', 'string', 'max:50'],
            'installed_on' => ['nullable', 'date', 'before_or_equal:today'],
            'driver' => ['required', Rule::in(['html', 'simulated'])],
            'endpoint' => ['nullable', 'required_if:driver,html', 'url:http,https', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
