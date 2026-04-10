<?php

namespace Database\Seeders;

use App\Models\Personnel;
use Illuminate\Database\Seeder;

class PersonnelSeeder extends Seeder
{
    /** Run the database seeds. */
    public function run(): void
    {
        Personnel::factory()->count(8)->create();
        Personnel::factory()->blockList()->count(2)->create();
    }
}
