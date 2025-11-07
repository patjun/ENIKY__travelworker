<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use App\Models\Listicle;
use App\Models\Location;
use App\Models\LocationBlock;
use App\Models\RelatedLinksBlock;
use Illuminate\Database\Seeder;

class ListicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some existing locations
        $locations = Location::take(10)->get();

        if ($locations->isEmpty()) {
            $this->command->warn('No locations found. Please seed locations first.');
            return;
        }

        // Create a sample listicle
        $listicle = Listicle::factory()->published()->create([
            'title_de' => '10 Sehenswürdigkeiten in Bratislava',
            'title_en' => '10 Attractions in Bratislava',
            'slug_de' => '10-sehenswuerdigkeiten-in-bratislava',
            'slug_en' => '10-attractions-in-bratislava',
            'intro_de' => '<p>Entdecken Sie die schönsten Orte in Bratislava, der Hauptstadt der Slowakei.</p>',
            'intro_en' => '<p>Discover the most beautiful places in Bratislava, the capital of Slovakia.</p>',
        ]);

        // Create German content blocks
        foreach ($locations->take(5) as $index => $location) {
            $locationBlock = LocationBlock::create([
                'location_id' => $location->id,
                'custom_intro' => '<p>Ein wunderbarer Ort zum Besuchen in Bratislava.</p>',
            ]);

            ContentBlock::create([
                'listicle_id' => $listicle->id,
                'blockable_type' => LocationBlock::class,
                'blockable_id' => $locationBlock->id,
                'order' => $index,
                'language' => 'de',
            ]);
        }

        // Create German related links block
        $relatedLinksBlockDe = RelatedLinksBlock::create([
            'title' => 'Das könnte Dich auch interessieren',
            'links' => [
                [
                    'title' => 'Hotels in Bratislava',
                    'url' => 'https://example.com/hotels-bratislava',
                ],
                [
                    'title' => 'Restaurants in Bratislava',
                    'url' => 'https://example.com/restaurants-bratislava',
                ],
                [
                    'title' => 'Stadtrundfahrten',
                    'url' => 'https://example.com/city-tours',
                ],
            ],
        ]);

        ContentBlock::create([
            'listicle_id' => $listicle->id,
            'blockable_type' => RelatedLinksBlock::class,
            'blockable_id' => $relatedLinksBlockDe->id,
            'order' => 5,
            'language' => 'de',
        ]);

        // Create English content blocks (different number to demonstrate flexibility)
        foreach ($locations->take(7) as $index => $location) {
            $locationBlock = LocationBlock::create([
                'location_id' => $location->id,
                'custom_intro' => '<p>A wonderful place to visit in Bratislava.</p>',
            ]);

            ContentBlock::create([
                'listicle_id' => $listicle->id,
                'blockable_type' => LocationBlock::class,
                'blockable_id' => $locationBlock->id,
                'order' => $index,
                'language' => 'en',
            ]);
        }

        // Create English related links block
        $relatedLinksBlockEn = RelatedLinksBlock::create([
            'title' => 'You might also be interested in',
            'links' => [
                [
                    'title' => 'Hotels in Bratislava',
                    'url' => 'https://example.com/hotels-bratislava',
                ],
                [
                    'title' => 'Restaurants in Bratislava',
                    'url' => 'https://example.com/restaurants-bratislava',
                ],
                [
                    'title' => 'City Tours',
                    'url' => 'https://example.com/city-tours',
                ],
            ],
        ]);

        ContentBlock::create([
            'listicle_id' => $listicle->id,
            'blockable_type' => RelatedLinksBlock::class,
            'blockable_id' => $relatedLinksBlockEn->id,
            'order' => 7,
            'language' => 'en',
        ]);

        // Generate HTML widgets
        $listicle->generateWidgets();

        $this->command->info('Listicle seeded successfully!');
        $this->command->info('Created 5 location blocks + 1 related links block for German');
        $this->command->info('Created 7 location blocks + 1 related links block for English');
    }
}
