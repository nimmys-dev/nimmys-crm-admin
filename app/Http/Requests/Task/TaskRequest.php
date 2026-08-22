<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'assigned_to' => [
                'required',
                'exists:users,id',
            ],

            'approved_by' => [
                'required',
                'exists:users,id',
            ],

            'task_type' => [
                'required',
                Rule::in([
                    'daily',
                    'weekly',
                    'monthly',
                    'quarterly',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Daily
            |--------------------------------------------------------------------------
            */

            'start_time' => [
                'nullable',
                'date_format:H:i',
                'required_if:task_type,daily',
            ],

            'end_time' => [
                'nullable',
                'date_format:H:i',
                'required_if:task_type,daily',
                'after:start_time',
            ],

            /*
            |--------------------------------------------------------------------------
            | Weekly
            |--------------------------------------------------------------------------
            */

            'week_start_day' => [
                'nullable',
                Rule::in([
                    'monday',
                    'tuesday',
                    'wednesday',
                    'thursday',
                    'friday',
                    'saturday',
                    'sunday',
                ]),
                'required_if:task_type,weekly',
            ],

            'week_end_day' => [
                'nullable',
                Rule::in([
                    'monday',
                    'tuesday',
                    'wednesday',
                    'thursday',
                    'friday',
                    'saturday',
                    'sunday',
                ]),
                'required_if:task_type,weekly',
            ],

            /*
            |--------------------------------------------------------------------------
            | Monthly
            |--------------------------------------------------------------------------
            */

            'monthly_start_date' => [
                'nullable',
                'date',
                'required_if:task_type,monthly',
            ],

            'monthly_end_date' => [
                'nullable',
                'date',
                'required_if:task_type,monthly',
                'after_or_equal:monthly_start_date',
            ],

            /*
            |--------------------------------------------------------------------------
            | Quarterly
            |--------------------------------------------------------------------------
            */

            'quarter' => [
                'nullable',
                Rule::in([
                    'q1',
                    'q2',
                    'q3',
                    'q4',
                ]),
                'required_if:task_type,quarterly',
            ],

            'quarter_start_date' => [
                'nullable',
                'date',
                'required_if:task_type,quarterly',
            ],

            'quarter_end_date' => [
                'nullable',
                'date',
                'required_if:task_type,quarterly',
                'after_or_equal:quarter_start_date',
            ],

            /*
            |--------------------------------------------------------------------------
            | Description
            |--------------------------------------------------------------------------
            */

            'description' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'in_progress',
                    'completed',
                ]),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'assigned_to' => 'assigned user',
            'approved_by' => 'approver',

            'week_start_day' => 'week start day',
            'week_end_day' => 'week end day',

            'monthly_start_date' => 'start date',
            'monthly_end_date' => 'end date',

            'quarter_start_date' => 'start date',
            'quarter_end_date' => 'end date',
        ];
    }
}