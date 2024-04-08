<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePurchaseOrderRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'po_number' => 'required|max:255',
            'buyer_name' => 'required|max:255',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:App\Models\PurchaseOrderItem,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.unit_price' => 'required|between:0,9999999999.99',
            'items.*.quantity' => 'required|integer',
            'items.*.category_id' => 'required|exists:App\Models\Category,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        $response =  response()->json([
            'message' => 'Invalid data',
            'details' => $errors->messages(),
        ], 422);

        throw new HttpResponseException($response);
    }
}
