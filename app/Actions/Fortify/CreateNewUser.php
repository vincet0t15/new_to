<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user and employee.
     *
     * Self-registration mirrors employee create without a photo. Both records
     * are written together so the login always has its employee details.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        $validated = Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'position_id' => ['required', 'integer', Rule::exists('positions', 'id')],
            'office_id' => ['required', 'integer', Rule::exists('offices', 'id')],
            'employment_type_id' => ['required', 'integer', Rule::exists('employment_types', 'id')],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_active' => ['boolean'],
            'username' => $this->usernameRules(),
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $isActive = (bool) ($validated['is_active'] ?? true);

        return DB::transaction(function () use ($validated, $isActive): User {
            $employee = Employee::create([
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
                'position_id' => $validated['position_id'],
                'office_id' => $validated['office_id'],
                'employment_type_id' => $validated['employment_type_id'],
                'contact_number' => $validated['contact_number'] ?? null,
                'monthly_salary' => $validated['monthly_salary'] ?? null,
                'is_active' => $isActive,
            ]);

            $user = User::create([
                'name' => $employee->full_name,
                'username' => Str::lower($validated['username']),
                'email' => $validated['email'] ?? null,
                'password' => $validated['password'],
                'is_active' => $isActive,
            ]);

            $user->employee()->associate($employee);
            $user->save();

            return $user;
        });
    }
}
