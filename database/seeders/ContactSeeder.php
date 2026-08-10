<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allTags = Tag::all();

        $contacts = Contact::factory()->count(20)->create();

        foreach ($contacts as $contact) {
            $rand = mt_rand(1, 3);
            $contact->tags()->attach($allTags->random($rand)->pluck('id')->toArray());
        }
    }
}
