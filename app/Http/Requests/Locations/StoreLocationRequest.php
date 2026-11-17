<?php
namespace App\Http\Requests\Locations;
use Illuminate\Foundation\Http\FormRequest;
class StoreLocationRequest extends FormRequest { public function rules(): array { return ['name'=>['required','string','max:100','unique:locations,name'],'description'=>['nullable','string','max:255'],'is_active'=>['boolean']]; } }
