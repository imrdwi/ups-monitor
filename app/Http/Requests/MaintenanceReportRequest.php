<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MaintenanceReportRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reported_on' => ['required', 'date', 'before_or_equal:today'],
            'work_permit_number' => ['nullable', 'string', 'max:50'],
            'room_temperature_celsius' => ['required', 'numeric', 'between:-10,70'],
            'backup_minutes' => ['required', 'integer', 'between:0,1440'],
            'load_percent' => ['required', 'numeric', 'between:0,150'],
            'battery_voltage_positive' => ['required', 'numeric', 'between:0,1000'],
            'battery_voltage_negative' => ['required', 'numeric', 'between:0,1000'],
            'battery_blocks' => ['required', 'integer', 'between:1,500'],
            'blocks_ok' => ['required', 'integer', 'min:0'],
            'blocks_low' => ['required', 'integer', 'min:0'],
            'blocks_bad' => ['required', 'integer', 'min:0'],
            'summary' => ['required', 'string', 'max:10000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,xlsx,docx', 'max:10240'],
        ];
    }

    /**
     * Every block must be counted exactly once.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $counted = $this->integer('blocks_ok') + $this->integer('blocks_low') + $this->integer('blocks_bad');

            if (! $validator->errors()->has('battery_blocks') && $counted !== $this->integer('battery_blocks')) {
                $validator->errors()->add('battery_blocks', __('OK, low and bad blocks add up to :counted, not :total.', [
                    'counted' => $counted,
                    'total' => $this->integer('battery_blocks'),
                ]));
            }
        }];
    }
}
