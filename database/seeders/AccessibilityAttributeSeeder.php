<?php

namespace Database\Seeders;

use App\Models\AccessibilityAttribute;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccessibilityAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attributes = [
            [
                'placeholder' => 'wheelchair_accessible_entrance',
                'name_en' => 'Wheelchair-accessible entrance',
                'name_de' => 'Rollstuhlgerechter Eingang',
                'description_en' => 'Indicates if the entrance is at least 3 feet (1 meter) wide and has no steps, or a permanent/movable ramp if there are steps. Revolving doors are not considered wheelchair-accessible.',
            ],
            [
                'placeholder' => 'wheelchair_accessible_restroom',
                'name_en' => 'Wheelchair-accessible restroom',
                'name_de' => 'Rollstuhlgerechte Toiletten',
                'description_en' => 'Specifies if the business has a restroom that is accessible to wheelchair users.',
            ],
            [
                'placeholder' => 'wheelchair_accessible_parking',
                'name_en' => 'Wheelchair-accessible parking',
                'name_de' => 'Rollstuhlgerechte Parkplätze',
                'description_en' => 'Indicates if accessible parking spaces are available.',
            ],
            [
                'placeholder' => 'wheelchair_accessible_elevator',
                'name_en' => 'Wheelchair-accessible elevator',
                'name_de' => 'Rollstuhlgerechter Aufzug',
                'description_en' => 'States whether an elevator is accessible to wheelchair users.',
            ],
            [
                'placeholder' => 'wheelchair_accessible_seating',
                'name_en' => 'Wheelchair-accessible seating',
                'name_de' => 'Rollstuhlgerechte Sitzplätze',
                'description_en' => 'Available for businesses with seating, like restaurants or waiting areas.',
            ],
            [
                'placeholder' => 'assistive_hearing_loop',
                'name_en' => 'Assistive hearing loop',
                'name_de' => 'Induktionsschleife',
                'description_en' => 'Indicates if a permanently installed hearing loop system is available for customers.',
            ],
            [
                'placeholder' => 'auracast_broadcast_audio',
                'name_en' => 'Auracast broadcast audio',
                'name_de' => 'Auracast-Audioübertragung',
                'description_en' => 'Shows if the business has a permanently installed Auracast broadcaster for compatible devices.',
            ],
            [
                'placeholder' => 'assisted_listening_devices',
                'name_en' => 'Assisted listening devices',
                'name_de' => 'Hörhilfen',
                'description_en' => 'Specifies if devices are available for customers to borrow.',
            ],
            [
                'placeholder' => 'beach_wheelchairs',
                'name_en' => 'Beach wheelchairs',
                'name_de' => 'Strand-Rollstühle',
                'description_en' => 'Available for businesses located near a beach.',
            ],
            [
                'placeholder' => 'mobility_scooter_rental',
                'name_en' => 'Mobility scooter rental',
                'name_de' => 'Elektromobil-Verleih',
                'description_en' => 'Indicates if mobility scooters are available for rent.',
            ],
            [
                'placeholder' => 'passenger_loading_area',
                'name_en' => 'Passenger loading area',
                'name_de' => 'Passagier-Ladezone',
                'description_en' => 'Applicable for businesses that have a designated area for passenger loading and unloading.',
            ],
        ];

        foreach ($attributes as $attribute) {
            AccessibilityAttribute::create($attribute);
        }
    }
}
