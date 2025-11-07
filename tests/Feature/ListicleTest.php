<?php

namespace Tests\Feature;

use App\Models\ContentBlock;
use App\Models\Listicle;
use App\Models\Location;
use App\Models\LocationBlock;
use App\Models\RelatedLinksBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_listicle(): void
    {
        $listicle = Listicle::factory()->create();

        $this->assertDatabaseHas('listicles', [
            'id' => $listicle->id,
            'title_de' => $listicle->title_de,
        ]);
    }

    public function test_can_create_location_block(): void
    {
        $location = Location::factory()->create();
        $locationBlock = LocationBlock::factory()->create([
            'location_id' => $location->id,
            'custom_intro' => '<p>Test intro</p>',
        ]);

        $this->assertDatabaseHas('location_blocks', [
            'id' => $locationBlock->id,
            'location_id' => $location->id,
        ]);
    }

    public function test_can_create_related_links_block(): void
    {
        $relatedLinksBlock = RelatedLinksBlock::factory()->create();

        $this->assertDatabaseHas('related_links_blocks', [
            'id' => $relatedLinksBlock->id,
        ]);
    }

    public function test_can_create_content_block_with_location(): void
    {
        $listicle = Listicle::factory()->create();
        $locationBlock = LocationBlock::factory()->create();

        $contentBlock = ContentBlock::create([
            'listicle_id' => $listicle->id,
            'blockable_type' => LocationBlock::class,
            'blockable_id' => $locationBlock->id,
            'order' => 0,
            'language' => 'de',
        ]);

        $this->assertDatabaseHas('content_blocks', [
            'id' => $contentBlock->id,
            'listicle_id' => $listicle->id,
            'language' => 'de',
        ]);

        $this->assertEquals($locationBlock->id, $contentBlock->blockable_id);
    }

    public function test_can_create_content_block_with_related_links(): void
    {
        $listicle = Listicle::factory()->create();
        $relatedLinksBlock = RelatedLinksBlock::factory()->create();

        $contentBlock = ContentBlock::create([
            'listicle_id' => $listicle->id,
            'blockable_type' => RelatedLinksBlock::class,
            'blockable_id' => $relatedLinksBlock->id,
            'order' => 0,
            'language' => 'de',
        ]);

        $this->assertDatabaseHas('content_blocks', [
            'id' => $contentBlock->id,
            'listicle_id' => $listicle->id,
        ]);

        $this->assertEquals($relatedLinksBlock->id, $contentBlock->blockable_id);
    }

    public function test_generates_html_for_listicle(): void
    {
        $listicle = Listicle::factory()->create([
            'intro_de' => '<p>Test intro</p>',
        ]);

        $location = Location::factory()->create([
            'contact_info_html' => '<div>Contact info</div>',
        ]);

        $locationBlock = LocationBlock::create([
            'location_id' => $location->id,
            'custom_intro' => '<p>Custom intro</p>',
        ]);

        ContentBlock::create([
            'listicle_id' => $listicle->id,
            'blockable_type' => LocationBlock::class,
            'blockable_id' => $locationBlock->id,
            'order' => 0,
            'language' => 'de',
        ]);

        $listicle->generateWidgets();

        $this->assertNotNull($listicle->generated_html_de);
        $this->assertStringContainsString('Test intro', $listicle->generated_html_de);
        $this->assertStringContainsString('Custom intro', $listicle->generated_html_de);
    }

    public function test_related_links_block_renders_html(): void
    {
        $relatedLinksBlock = RelatedLinksBlock::factory()->create([
            'title' => 'Test Title',
            'links' => [
                [
                    'title' => 'Link 1',
                    'url' => 'https://example.com/link1',
                ],
            ],
        ]);

        $html = $relatedLinksBlock->renderHtml('de');

        $this->assertStringContainsString('Test Title', $html);
        $this->assertStringContainsString('Link 1', $html);
        $this->assertStringContainsString('https://example.com/link1', $html);
    }

    public function test_can_have_different_blocks_per_language(): void
    {
        $listicle = Listicle::factory()->create();
        $location = Location::factory()->create();

        // Create German blocks
        $locationBlockDe = LocationBlock::create([
            'location_id' => $location->id,
            'custom_intro' => '<p>Deutsche Intro</p>',
        ]);

        ContentBlock::create([
            'listicle_id' => $listicle->id,
            'blockable_type' => LocationBlock::class,
            'blockable_id' => $locationBlockDe->id,
            'order' => 0,
            'language' => 'de',
        ]);

        // Create English blocks (different number)
        $locationBlockEn1 = LocationBlock::create([
            'location_id' => $location->id,
            'custom_intro' => '<p>English Intro 1</p>',
        ]);

        $locationBlockEn2 = LocationBlock::create([
            'location_id' => $location->id,
            'custom_intro' => '<p>English Intro 2</p>',
        ]);

        ContentBlock::create([
            'listicle_id' => $listicle->id,
            'blockable_type' => LocationBlock::class,
            'blockable_id' => $locationBlockEn1->id,
            'order' => 0,
            'language' => 'en',
        ]);

        ContentBlock::create([
            'listicle_id' => $listicle->id,
            'blockable_type' => LocationBlock::class,
            'blockable_id' => $locationBlockEn2->id,
            'order' => 1,
            'language' => 'en',
        ]);

        // Verify different number of blocks per language
        $this->assertEquals(1, $listicle->contentBlocks()->where('language', 'de')->count());
        $this->assertEquals(2, $listicle->contentBlocks()->where('language', 'en')->count());
    }
}
