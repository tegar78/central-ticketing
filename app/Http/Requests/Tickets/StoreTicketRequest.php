<?php

declare(strict_types=1);

namespace App\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'billing_instance_id' => ['sometimes', 'required', 'exists:billing_instances,id'],
            'remote_ticket_id'    => ['nullable', 'string', 'max:100'],
            'no_services'         => ['required', 'string', 'max:100'],
            'customer_name'       => ['required', 'string', 'max:255'],
            'customer_phone'      => ['nullable', 'string', 'max:25'],
            'customer_address'    => ['nullable', 'string'],
            'latitude'            => ['nullable', 'string', 'max:50'],
            'longitude'           => ['nullable', 'string', 'max:50'],
            'category_name'       => ['nullable', 'string', 'max:100'],
            'problem_description' => ['required', 'string'],
            'picture'             => ['nullable', 'string'],
            'created_by_name'     => ['nullable', 'string', 'max:100'],
            'created_by_role'     => ['nullable', 'string', 'max:50'],
        ];
    }
}
