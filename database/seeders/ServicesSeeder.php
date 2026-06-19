<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'slug'         => 'ticket',
                'label'        => ['en' => 'Ticket booking', 'it' => 'Biglietti', 'bn' => 'টিকিট বুকিং'],
                'prompt_label' => 'booking a travel ticket',
                'color'        => 'primary',
                'icon'         => 'heroicon-o-ticket',
                'is_active'    => true,
                'sort_order'   => 1,
                'tool_name'    => 'submit_ticket_request',
                'tool_description' => 'Save a completed ticket booking request once all required details are collected and the customer has confirmed.',
                'tool_fields'  => [
                    ['name' => 'full_name',   'type' => 'string',  'required' => true,  'description' => "Customer's full name"],
                    ['name' => 'route',       'type' => 'string',  'required' => true,  'description' => 'From and to, e.g. Bologna to Rome'],
                    ['name' => 'travel_date', 'type' => 'string',  'required' => true,  'description' => 'Date of travel, format YYYY-MM-DD'],
                    ['name' => 'passengers',  'type' => 'integer', 'required' => true,  'description' => 'Number of passengers'],
                    ['name' => 'notes',       'type' => 'string',  'required' => false, 'description' => 'Any extra preferences'],
                ],
            ],
            [
                'slug'         => 'license',
                'label'        => ['en' => 'Driving license', 'it' => 'Patente', 'bn' => 'ড্রাইভিং লাইসেন্স'],
                'prompt_label' => 'a driving license enquiry',
                'color'        => 'warning',
                'icon'         => 'heroicon-o-identification',
                'is_active'    => true,
                'sort_order'   => 2,
                'tool_name'    => 'submit_license_request',
                'tool_description' => 'Save a completed driving license enquiry once all required details are collected and confirmed.',
                'tool_fields'  => [
                    ['name' => 'full_name',    'type' => 'string', 'required' => true,  'description' => "Customer's full name"],
                    ['name' => 'request_type', 'type' => 'string', 'required' => true,  'description' => 'New, renewal, or foreign conversion'],
                    ['name' => 'nationality',  'type' => 'string', 'required' => true,  'description' => 'Customer nationality'],
                    ['name' => 'phone',        'type' => 'string', 'required' => false, 'description' => 'Alternate contact number'],
                ],
            ],
            [
                'slug'         => 'immigration',
                'label'        => ['en' => 'Immigration', 'it' => 'Immigrazione', 'bn' => 'ইমিগ্রেশন'],
                'prompt_label' => 'an immigration enquiry',
                'color'        => 'success',
                'icon'         => 'heroicon-o-globe-alt',
                'is_active'    => true,
                'sort_order'   => 3,
                'tool_name'    => 'submit_immigration_request',
                'tool_description' => 'Save a completed immigration enquiry once all required details are collected and confirmed.',
                'tool_fields'  => [
                    ['name' => 'full_name',      'type' => 'string', 'required' => true,  'description' => "Customer's full name"],
                    ['name' => 'enquiry_type',   'type' => 'string', 'required' => true,  'description' => 'e.g. work permit, family reunification, citizenship'],
                    ['name' => 'nationality',    'type' => 'string', 'required' => true,  'description' => 'Customer nationality'],
                    ['name' => 'current_status', 'type' => 'string', 'required' => false, 'description' => 'Current visa or residence status'],
                ],
            ],
        ];

        foreach ($services as $data) {
            Service::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
