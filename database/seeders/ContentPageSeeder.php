<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use App\Models\ContentPage;
use App\Models\Location;
use App\Models\RelatedLinksBlock;
use Illuminate\Database\Seeder;

class ContentPageSeeder extends Seeder
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

        // Create a sample content page
        $contentPage = ContentPage::factory()->published()->create([
            'title_de' => '10 Sehenswürdigkeiten in Bratislava',
            'title_en' => '10 Attractions in Bratislava',
            'slug_de' => '10-sehenswuerdigkeiten-in-bratislava',
            'slug_en' => '10-attractions-in-bratislava',
            'intro_de' => '<p>Entdecken Sie die schönsten Orte in Bratislava, der Hauptstadt der Slowakei.</p>',
            'intro_en' => '<p>Discover the most beautiful places in Bratislava, the capital of Slovakia.</p>',
        ]);

        // Attach locations to the content page
        foreach ($locations as $index => $location) {
            $contentPage->locations()->attach($location->id, [
                'order' => $index,
                'custom_intro_de' => '<p>Ein wunderbarer Ort zum Besuchen in Bratislava.</p>',
                'custom_intro_en' => '<p>A wonderful place to visit in Bratislava.</p>',
            ]);
        }

        // Create a related links block
        $relatedLinksBlock = RelatedLinksBlock::factory()->create([
            'title_de' => 'Das könnte Dich auch interessieren',
            'title_en' => 'You might also be interested in',
            'links' => [
                [
                    'title_de' => 'Hotels in Bratislava',
                    'title_en' => 'Hotels in Bratislava',
                    'url' => 'https://example.com/hotels-bratislava',
                ],
                [
                    'title_de' => 'Restaurants in Bratislava',
                    'title_en' => 'Restaurants in Bratislava',
                    'url' => 'https://example.com/restaurants-bratislava',
                ],
                [
                    'title_de' => 'Stadtrundfahrten',
                    'title_en' => 'City Tours',
                    'url' => 'https://example.com/city-tours',
                ],
            ],
        ]);

        // Create content blocks for both languages
        ContentBlock::create([
            'content_page_id' => $contentPage->id,
            'blockable_type' => RelatedLinksBlock::class,
            'blockable_id' => $relatedLinksBlock->id,
            'order' => 0,
            'language' => 'de',
        ]);

        ContentBlock::create([
            'content_page_id' => $contentPage->id,
            'blockable_type' => RelatedLinksBlock::class,
            'blockable_id' => $relatedLinksBlock->id,
            'order' => 0,
            'language' => 'en',
        ]);

        // Generate HTML widgets
        $contentPage->generateWidgets();

        $this->command->info('ContentPage seeded successfully!');
    }
}
