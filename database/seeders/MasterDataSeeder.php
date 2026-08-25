<?php

namespace Database\Seeders;

use App\Models\BeneficiarySector;
use App\Models\ConvergenceProgram;
use App\Models\Livelihood;
use App\Models\ProjectPurpose;
use App\Models\ProjectType;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedProjectTypes();
        $this->seedProjectPurposes();
        $this->seedBeneficiarySectors();
        $this->seedLivelihoods();
        $this->seedConvergencePrograms();
    }

    private function seedProjectTypes(): void
    {
        $items = [
            [
                'code' => 'INDIVIDUAL',
                'name' => 'Individual',
                'sort_order' => 10,
            ],
            [
                'code' => 'GROUP',
                'name' => 'Group',
                'sort_order' => 20,
            ],
        ];

        foreach ($items as $item) {
            ProjectType::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }
    }

    private function seedProjectPurposes(): void
    {
        $items = [
            [
                'code' => 'FORMATION',
                'name' => 'Formation',
                'sort_order' => 10,
            ],
            [
                'code' => 'ENHANCEMENT',
                'name' => 'Enhancement',
                'sort_order' => 20,
            ],
        ];

        foreach ($items as $item) {
            ProjectPurpose::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }
    }

    private function seedBeneficiarySectors(): void
    {
        $items = [
            'Parents / Guardians of Child Laborers',
            'Senior Citizens',
            'Persons with Disabilities',
            'Solo Parents',
            'Indigenous Peoples',
            'Youth',
            'Persons Deprived of Liberty',
            'Families of Persons Deprived of Liberty',
            'Parolees',
            'Probationers',
            'Pardoners',
            'Rebel Returnees / Former Rebels',
            'KIA / WIA / Families',
            'Persons Who Used Drugs',
            'Exited 4Ps Beneficiaries',
            'Graduation Approach - DILP',
            'TESDA Graduates',
            '4Ps Parents of Child Laborers',
            'El Niño Affected Workers',
            'Geographically Isolated and Disadvantaged Areas',
        ];

        foreach ($items as $index => $name) {
            BeneficiarySector::updateOrCreate(
                ['name' => $name],
                [
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedLivelihoods(): void
    {
        $items = [
            'Furniture Making',
            'Carpentry',
            'Construction Painting',
            'Masonry',
            'Baking',
            'Dressmaking',
            'Electrical',
            'Laundry',
            'Tile Setting',
            'Welding',
            'Fishing',
            'Fish Vending',
            'Meat Vending',
            'Meat Processing',
            'Nego-Kart',
            'Food Vending',
            'Vulcanizing',
        ];

        foreach ($items as $index => $name) {
            Livelihood::updateOrCreate(
                ['name' => $name],
                [
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedConvergencePrograms(): void
    {
        $items = [
            'EnTSUPERneur',
            'TAV',
            'WODP',
            'TUPAD Referral',
            'NIA',
            'KADIWA',
            'DSWD SLP',
        ];

        foreach ($items as $index => $name) {
            ConvergenceProgram::updateOrCreate(
                ['name' => $name],
                [
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ]
            );
        }
    }
}