<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * The municipal office directory.
 *
 * The list is the organization's own data, not sample data: every office the
 * travel order system may route through is enumerated here with its official
 * code. A few offices are already known to older installs by a shorter name
 * ("Mayor's Office"); those rows are folded into the official name in place,
 * so employees and workflow steps that already point at them keep working.
 */
class OfficeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * @var array<int, array{name: string, code: string, aliases?: array<int, string>}>
     */
    private const OFFICES = [
        ['name' => 'Commission on Elections', 'code' => 'COMELEC'],
        [
            'name' => 'Office of the Human Resource Management',
            'code' => 'HRMO',
            'aliases' => ['Human Resource Management Office'],
        ],
        ['name' => 'Office of the Municipal Accountant', 'code' => 'MACCO'],
        ['name' => 'Office of the Municipal Agriculture Biosystem Engineer', 'code' => 'OMABE'],
        ['name' => 'Office of the Municipal Agriculturist', 'code' => 'MAO'],
        ['name' => 'Office of the Municipal Assessor', 'code' => 'MASSO'],
        [
            'name' => 'Office of the Municipal Budget',
            'code' => 'MBO',
            'aliases' => ['Budget Office'],
        ],
        ['name' => 'Office of the Municipal Civil Registrar', 'code' => 'MCRO'],
        ['name' => 'Office of the Municipal Cooperative Development Officer', 'code' => 'MCDO'],
        ['name' => 'Office of the Municipal Disaster Risk Reduction and Management Officer', 'code' => 'MDRRMO'],
        ['name' => 'Office of the Municipal Economic Enterprise and Development Officer', 'code' => 'MEEDO'],
        ['name' => 'Office of the Municipal Engineer', 'code' => 'MEO'],
        ['name' => 'Office of the Municipal Environment and Natural Resources Officer', 'code' => 'MENRO'],
        ['name' => 'Office of the Municipal General Services Officer', 'code' => 'GSO'],
        ['name' => 'Office of the Municipal Health Officer', 'code' => 'MHO'],
        [
            'name' => 'Office of the Municipal Mayor',
            'code' => 'MO',
            'aliases' => ["Mayor's Office"],
        ],
        ['name' => 'Office of the Municipal Planning and Development Coordinator', 'code' => 'MPDO'],
        ['name' => 'Office of the Municipal Social Welfare and Development Officer', 'code' => 'MSWDO'],
        ['name' => 'Office of the Municipal Tourism Officer', 'code' => 'OMT'],
        ['name' => 'Office of the Municipal Treasurer', 'code' => 'TO'],
        [
            'name' => 'Office of the Sangguniang Bayan',
            'code' => 'SBO',
            'aliases' => ['Legislative Office'],
        ],
    ];

    public function run(): void
    {
        foreach (self::OFFICES as $definition) {
            $this->seedOffice($definition);
        }
    }

    /**
     * Create or normalise one office without disturbing its references.
     *
     * @param  array{name: string, code: string, aliases?: array<int, string>}  $definition
     */
    private function seedOffice(array $definition): void
    {
        $office = Office::query()
            ->where('office_name', $definition['name'])
            ->first();

        $matchedAlias = false;

        if ($office === null) {
            foreach ($definition['aliases'] ?? [] as $alias) {
                $office = Office::query()->where('office_name', $alias)->first();

                if ($office !== null) {
                    $matchedAlias = true;

                    break;
                }
            }
        }

        if ($office === null) {
            Office::firstOrCreate(
                ['office_name' => $definition['name']],
                ['office_code' => $definition['code']],
            );

            return;
        }

        $office->fill([
            'office_name' => $definition['name'],
            'office_code' => $matchedAlias
                ? $definition['code']
                : ($office->office_code ?? $definition['code']),
        ]);

        if ($office->isDirty()) {
            $office->save();
        }
    }
}
