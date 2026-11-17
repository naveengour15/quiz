<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuizColorHobbyInterestGameSeeder extends Seeder
{
    public function run(): void
    {
        $now = now(); // aaj ki date

        DB::table('quiz_color')->insert([
            ['name' => 'Red', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Blue', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Green', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Yellow', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Orange', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Purple', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Pink', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Black', 'status' => 'active', 'created_at' => $now],
            ['name' => 'White', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Brown', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Grey', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Gold', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Silver', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Maroon', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Indigo', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Violet', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Teal', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Navy Blue', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Sky Blue', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Olive', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Turquoise', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Lavender', 'status' => 'active', 'created_at' => $now],
        ]);

        DB::table('quiz_hobby')->insert([
            ['name' => 'Reading', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Travelling', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Photography', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Painting', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Drawing', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Singing', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Dancing', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Cooking', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Baking', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Gardening', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Writing', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Blogging', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Gaming', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Cycling', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Yoga', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Meditation', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Fishing', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Camping', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Hiking', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Listening to Music', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Watching Movies', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Crafting', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Calligraphy', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Bird Watching', 'status' => 'active', 'created_at' => $now],
        ]);

        DB::table('quiz_interest')->insert([
            ['name' => 'Technology', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Science', 'status' => 'active', 'created_at' => $now],
            ['name' => 'History', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Geography', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Current Affairs', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Sports', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Movies', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Music', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Business', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Finance', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Education', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Travel', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Artificial Intelligence', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Space', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Environment', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Health & Fitness', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Fashion', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Food & Cuisine', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Automobiles', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Cryptocurrency', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Startups', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Literature', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Psychology', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Politics', 'status' => 'active', 'created_at' => $now],
        ]);

        DB::table('quiz_game')->insert([
            ['name' => 'Cricket', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Football', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Basketball', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Volleyball', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Tennis', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Badminton', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Chess', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Carrom', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Ludo', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Kabaddi', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Hockey', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Table Tennis', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Boxing', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Wrestling', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Golf', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Racing', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Esports', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Archery', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Swimming', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Athletics', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Rugby', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Baseball', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Snooker', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Bowling', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Skating', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Kho-Kho', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Fencing', 'status' => 'active', 'created_at' => $now],
            ['name' => 'Gymnastics', 'status' => 'active', 'created_at' => $now],
        ]);
    }
}