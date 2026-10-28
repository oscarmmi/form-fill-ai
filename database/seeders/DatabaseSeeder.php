<?php

namespace Database\Seeders;

use App\Models\DemoMessage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $messages = [
            [
                'title' => 'Beacon sensor is offline',
                'raw_text' => 'Hi, I’m Maya Chen (maya.chen@example.test). My Beacon Sensor stopped syncing after the update and has been offline since this morning.',
            ],
            [
                'title' => 'Mobile app keeps crashing',
                'raw_text' => 'Hello support, I am Jordan Lee and my email is jordan.lee@example.test. Harbor Desk Mobile crashes whenever I open the dispatch board. This is blocking my team today.',
            ],
            [
                'title' => 'Question about Dockside reports',
                'raw_text' => 'My name is Avery Patel. You can reach me at avery.patel@example.test. I have a question about exporting a weekly report from Dockside. No rush, when you can.',
            ],
            [
                'title' => 'Atlas Reader will not connect',
                'raw_text' => 'Hi, I’m Riley Morgan. Our Atlas Reader will not connect to the office network after we changed routers. Could someone help me troubleshoot?',
            ],
        ];

        foreach ($messages as $message) {
            DemoMessage::query()->updateOrCreate(['title' => $message['title']], $message);
        }
    }
}
