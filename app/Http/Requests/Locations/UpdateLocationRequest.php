<?php
namespace App\Http\Requests\Locations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateLocationRequest extends FormRequest { public function rules(): array { $m=$this->route('location'); return ['name'=>['required','string','max:100',Rule::unique('locations','name')->ignore($m?->id)],'description'=>['nullable','string','max:255'],'is_active'=>['boolean']]; } }
