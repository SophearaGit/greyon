<?php

namespace Database\Seeders;

use App\Models\Enquiry;
use Illuminate\Database\Seeder;

class EnquirySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'name' => 'Sophea Lim',
                'email' => 'sophea.lim@example.com',
                'phone' => '+855 12 111 222',
                'subject' => 'Group stay in Phnom Penh',
                'message' => 'Looking for 6 rooms in October for a family gathering.',
                'consent' => true,
                'status' => 'new',
            ],
            [
                'name' => 'James Carter',
                'email' => 'james.carter@example.com',
                'phone' => '+1 415 555 0199',
                'subject' => 'Airport transfer Angkor',
                'message' => 'Can you arrange transfer from REP for two adults?',
                'consent' => true,
                'status' => 'in_progress',
                'internal_notes' => 'Quoted private car — waiting on guest reply.',
            ],
            [
                'name' => 'Closed Guest',
                'email' => 'closed@example.com',
                'phone' => '+855 10 000 000',
                'subject' => 'Invoice copy',
                'message' => 'Please resend last month’s invoice.',
                'consent' => true,
                'status' => 'closed',
                'internal_notes' => 'Sent PDF.',
            ],
        ];

        foreach ($rows as $row) {
            Enquiry::firstOrCreate(
                [
                    'email' => $row['email'],
                    'subject' => $row['subject'],
                ],
                $row
            );
        }
    }
}
