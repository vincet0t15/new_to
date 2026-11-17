<?php
namespace App\Http\Requests\TravelClassifications;
use Illuminate\Foundation\Http\FormRequest;
class StoreTravelClassificationRequest extends FormRequest { public function rules(): array { return ['name'=>['required','string','max:100','unique:travel_classifications,name'],'description'=>['nullable','string','max:255'],'is_active'=>['boolean']]; } }
