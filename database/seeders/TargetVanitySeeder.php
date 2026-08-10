<?php

namespace Database\Seeders;

use App\Models\TargetVanity;
use Illuminate\Database\Seeder;

class TargetVanitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vanities = [
            // CCMA
            [
                'target_name' => 'sabinet_ccma',
                'vanity_name' => 'CCMA Awards',
                'target_type' => 'cases',
            ],
            // Courts (cases)
            [
                'target_name' => 'ZACC',
                'vanity_name' => 'Constitutional Court of South Africa',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZASCA',
                'vanity_name' => 'Supreme Court of Appeal of South Africa',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAGPPHC',
                'vanity_name' => 'Gauteng High Court, Pretoria',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAGPJHC',
                'vanity_name' => 'Gauteng High Court, Johannesburg',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAWCHC',
                'vanity_name' => 'Western Cape High Court, Cape Town',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAFSHC',
                'vanity_name' => 'Free State High Court, Bloemfontein',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAKZNDHC',
                'vanity_name' => 'KwaZulu-Natal High Court, Durban',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAKZNHC',
                'vanity_name' => 'KwaZulu-Natal High Court, Pietermaritzburg',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAECGHC',
                'vanity_name' => 'Eastern Cape High Court, Grahamstown',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAECPEHC',
                'vanity_name' => 'Eastern Cape High Court, Port Elizabeth',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAECELHC',
                'vanity_name' => 'Eastern Cape High Court, East London',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAECBHC',
                'vanity_name' => 'Eastern Cape High Court, Bhisho',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZALMPPHC',
                'vanity_name' => 'Limpopo High Court, Polokwane',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZANWHC',
                'vanity_name' => 'North West High Court, Mahikeng',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZANCHC',
                'vanity_name' => 'Northern Cape High Court, Kimberley',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZALC',
                'vanity_name' => 'Labour Court of South Africa',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZALAC',
                'vanity_name' => 'Labour Appeal Court of South Africa',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZACAC',
                'vanity_name' => 'Competition Appeal Court of South Africa',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAEQC',
                'vanity_name' => 'Equality Court of South Africa',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZALCC',
                'vanity_name' => 'Land Claims Court of South Africa',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZATC',
                'vanity_name' => 'Tax Court of South Africa',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAECC',
                'vanity_name' => 'Electoral Court of South Africa',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZALCJHB',
                'vanity_name' => 'Labour Court, Johannesburg',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZALCPE',
                'vanity_name' => 'Labour Court, Port Elizabeth',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZALCCT',
                'vanity_name' => 'Labour Court, Cape Town',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZALCD',
                'vanity_name' => 'Labour Court, Durban',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZALMPTHC',
                'vanity_name' => 'Limpopo High Court, Thohoyandou',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAMPMHC',
                'vanity_name' => 'Mpumalanga High Court, Middelburg',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAMPMBHC',
                'vanity_name' => 'Mpumalanga High Court, Mbombela',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAKZDHC',
                'vanity_name' => 'KwaZulu-Natal High Court, Durban',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAKZPHC',
                'vanity_name' => 'KwaZulu-Natal High Court, Pietermaritzburg',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAGPHC',
                'vanity_name' => 'Gauteng High Court',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAKZHC',
                'vanity_name' => 'KwaZulu-Natal High Court',
                'target_type' => 'cases',
            ],
            [
                'target_name' => 'ZAECHC',
                'vanity_name' => 'Eastern Cape High Court',
                'target_type' => 'cases',
            ],
            // Gazettes (gaz)
            [
                'target_name' => 'ZAGovGaz',
                'vanity_name' => 'South African Government Gazette',
                'target_type' => 'gaz',
            ],
            [
                'target_name' => 'ZAGPPrGaz',
                'vanity_name' => 'Gauteng Provincial Gazette',
                'target_type' => 'gaz',
            ],
            [
                'target_name' => 'ZAECPrGaz',
                'vanity_name' => 'Eastern Cape Provincial Gazette',
                'target_type' => 'gaz',
            ],
            [
                'target_name' => 'ZAKZPrGaz',
                'vanity_name' => 'KwaZulu-Natal Provincial Gazette',
                'target_type' => 'gaz',
            ],
            [
                'target_name' => 'ZAWCPrGaz',
                'vanity_name' => 'Western Cape Provincial Gazette',
                'target_type' => 'gaz',
            ],
            [
                'target_name' => 'ZAFSPrGaz',
                'vanity_name' => 'Free State Provincial Gazette',
                'target_type' => 'gaz',
            ],
            [
                'target_name' => 'ZALPPrGaz',
                'vanity_name' => 'Limpopo Provincial Gazette',
                'target_type' => 'gaz',
            ],
            [
                'target_name' => 'ZANWPrGaz',
                'vanity_name' => 'North West Provincial Gazette',
                'target_type' => 'gaz',
            ],
            [
                'target_name' => 'ZAMPPrGaz',
                'vanity_name' => 'Mpumalanga Provincial Gazette',
                'target_type' => 'gaz',
            ],
            [
                'target_name' => 'ZANCPrGaz',
                'vanity_name' => 'Northern Cape Provincial Gazette',
                'target_type' => 'gaz',
            ],
            [
                'target_name' => 'ZALMPrGaz',
                'vanity_name' => 'Limpopo Provincial Gazette',
                'target_type' => 'gaz',
            ],
            // Journals (journals)
            [
                'target_name' => 'ADRY',
                'vanity_name' => 'African Disability Rights Yearbook',
                'target_type' => 'journals',
            ],
            [
                'target_name' => 'AHRLJ',
                'vanity_name' => 'African Human Rights Law Journal',
                'target_type' => 'journals',
            ],
            [
                'target_name' => 'LDD',
                'vanity_name' => 'Law, Democracy & Development',
                'target_type' => 'journals',
            ],
            [
                'target_name' => 'PER',
                'vanity_name' => 'Potchefstroom Electronic Law Journal',
                'target_type' => 'journals',
            ],
            [
                'target_name' => 'DEJURE',
                'vanity_name' => 'De Jure Law Journal',
                'target_type' => 'journals',
            ],
            [
                'target_name' => 'DEREBUS',
                'vanity_name' => 'De Rebus Journal',
                'target_type' => 'journals',
            ],
        ];

        foreach ($vanities as $v) {
            TargetVanity::updateOrCreate(
                ['target_name' => $v['target_name']],
                ['vanity_name' => $v['vanity_name'], 'target_type' => $v['target_type']]
            );
        }
    }
}
