<?php

declare(strict_types=1);

namespace Naxas\RestaurantOps\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

final class SettlePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:1'], 'discount' => ['nullable', 'array'], 'discount.type' => ['nullable', 'in:percent,flat'], 'discount.value' => ['nullable', 'regex:/^\d+(?:\.\d{1,4})?$/'], 'discount.amount' => ['nullable', 'regex:/^\d+(?:\.\d{1,4})?$/'], 'tenders' => ['required', 'array', 'min:1', 'max:10'], 'tenders.*.method' => ['required', 'in:cash,card,mobile'], 'tenders.*.amount' => ['required', 'regex:/^\d+(?:\.\d{1,4})?$/'], 'tenders.*.provider' => ['nullable', 'string', 'max:64'], 'tenders.*.reference' => ['nullable', 'string', 'max:191'], 'tenders.*.note' => ['nullable', 'string', 'max:500']];
    }
}
