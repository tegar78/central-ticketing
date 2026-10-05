<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncCustomerRequest extends FormRequest
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
     * Supports both single customer JSON object and batch customer array payloads.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // If request payload is a numeric array of objects (batch upload)
        if ($this->isJson() && is_array($this->json()->all()) && array_is_list($this->json()->all())) {
            if (count($this->json()->all()) > 1000) {
                abort(422, 'Ukuran batch data pelanggan terlalu besar. Maksimum 1000 pelanggan per permintaan sinkronisasi.');
            }

            return [
                '*.remote_customer_id' => 'required',
                '*.no_services' => 'required|string|max:50',
                '*.name' => 'required|string|max:255',
                '*.phone' => 'nullable|string|max:25',
                '*.address' => 'nullable|string',
                '*.odp_name' => 'nullable|string|max:100',
                '*.latitude' => 'nullable|string|max:50',
                '*.longitude' => 'nullable|string|max:50',
                '*.package_name' => 'nullable|string|max:100',
                '*.monthly_fee' => 'nullable|numeric',
                '*.status' => 'nullable|in:active,isolated,inactive',
                '*.port_number' => 'nullable|integer',
                '*.no_port_odp' => 'nullable|integer',
                '*.ip_address' => 'nullable|string|max:45',
                '*.pppoe_user' => 'nullable|string|max:128',
            ];
        }

        // Single object or standard request fields
        return [
            'remote_customer_id' => 'required',
            'no_services' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:25',
            'address' => 'nullable|string',
            'odp_name' => 'nullable|string|max:100',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'package_name' => 'nullable|string|max:100',
            'monthly_fee' => 'nullable|numeric',
            'status' => 'nullable|in:active,isolated,inactive',
            'port_number' => 'nullable|integer',
            'no_port_odp' => 'nullable|integer',
            'ip_address' => 'nullable|string|max:45',
            'pppoe_user' => 'nullable|string|max:128',
        ];
    }
}
