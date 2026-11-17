<?php
namespace App\Http\Requests\FundSources;
use Illuminate\Foundation\Http\FormRequest;
class StoreFundSourceRequest extends FormRequest { public function rules(): array { return ['name'=>['required','string','max:100','unique:fund_sources,name'],'description'=>['nullable','string','max:255'],'is_active'=>['boolean']]; } }
