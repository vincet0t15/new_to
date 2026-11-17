<?php
namespace App\Http\Requests\TravelClassifications;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateTravelClassificationRequest extends FormRequest { public function rules(): array { $m=$this->route('travelClassification'); return ['name'=>['required','string','max:100',Rule::unique('travel_classifications','name')->ignore($m?->id)],'description'=>['nullable','string','max:255'],'is_active'=>['boolean']]; } }
