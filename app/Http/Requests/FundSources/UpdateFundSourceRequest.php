<?php

namespace App\Http\Requests\FundSources;

use App\Models\FundSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFundSourceRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $fundSource = $this->route('fundSource');
        $fundSourceId = $fundSource instanceof FundSource ? $fundSource->id : null;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('fund_sources', 'name')->ignore($fundSourceId)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
