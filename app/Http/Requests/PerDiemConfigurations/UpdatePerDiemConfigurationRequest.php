<?php
namespace App\Http\Requests\PerDiemConfigurations;
use Illuminate\Foundation\Http\FormRequest;
class UpdatePerDiemConfigurationRequest extends FormRequest { public function rules(): array { return ['name'=>['required','string','max:100'],'effective_from'=>['nullable','date'],'effective_until'=>['nullable','date','after_or_equal:effective_from'],'daily_rate'=>['nullable','numeric','min:0'],'is_active'=>['boolean']]; } }
