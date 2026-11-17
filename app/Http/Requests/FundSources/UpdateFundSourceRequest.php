<?php
namespace App\Http\Requests\FundSources;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateFundSourceRequest extends FormRequest { public function rules(): array { $m=$this->route('fundSource'); return ['name'=>['required','string','max:100',Rule::unique('fund_sources','name')->ignore($m?->id)],'description'=>['nullable','string','max:255'],'is_active'=>['boolean']]; } }
