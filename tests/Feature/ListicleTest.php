<?php

namespace Tests\Feature;

use App\Models\Attraction;
use App\Models\AttractionBlock;
use App\Models\ContentBlock;
use App\Models\Listicle;
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

    public function test_can_create_attraction_block(): void
    {
        $attraction = Attraction::factory()->create();
        $attractionBlock = AttractionBlock::factory()->create([
            'attraction_id' => $attraction->id,
            'custom_intro' => '<p>Test intro</p>',
        ]);

        $this->assertDatabaseHas('attraction_blocks', [
            'id' => $attractionBlock->id,
            'attraction_id' => $attraction->id,
        ]);
    }

    public function test_can_create_related_links_block(): void
    {
        $relatedLinksBlock = RelatedLinksBlock::factory()->create();

        $this->assertDatabaseHas('related_links_blocks', [
            'id' => $relatedLinksBlock->id,
        ]);
    }

    public function test_can_create_content_block_with_attraction(): void
    {
        $listicle = Listicle::factory()->create();
        $attractionBlock = AttractionBlock::factory()->create();

        $contentBlock = ContentBlock::create([
            'listicle_id' => $listicle->id,
            'blockable_type' => AttractionBlock::class,
            'blockable_id' => $attractionBlock->id,
            'order' => 0,
            'language' => 'de',
        ]);

        $this->assertDatabaseHas('content_blocks', [
            'id' => $contentBlock->id,
            'listicle_id' => $listicle->id,
            'language' => 'de',
        ]);

        $this->assertEquals($attractionBlock->id, $contentBlock->blockable_id);
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

        $attraction = Attraction::factory()->create([
            'contact_info_html' => '<div>Contact info</div>',
        ]);

        $attractionBlock = AttractionBlock::create([
            'attraction_id' => $attraction->id,
            'custom_intro' => '<p>Custom intro</p>',
        ]);

        ContentBlock::create([
            'listicle_id' => $listicle->id,
            'blockable_type' => AttractionBlock::class,
            'blockable_id' => $attractionBlock->id,
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
        $attraction = Attraction::factory()->create();

        // Create German blocks
        $attractionBlockDe = AttractionBlock::create([
            'attraction_id' => $attraction->id,
            'custom_intro' => '<p>Deutsche Intro</p>',
        ]);

        ContentBlock::create([
            'listicle_id' => $listicle->id,
            'blockable_type' => AttractionBlock::class,
            'blockable_id' => $attractionBlockDe->id,
            'order' => 0,
            'language' => 'de',
        ]);

        // Create English blocks (different number)
        $attractionBlockEn1 = AttractionBlock::create([
            'attraction_id' => $attraction->id,
            'custom_intro' => '<p>English Intro 1</p>',
        ]);

        $attractionBlockEn2 = AttractionBlock::create([
            'attraction_id' => $attraction->id,
            'custom_intro' => '<p>English Intro 2</p>',
        ]);

        ContentBlock::create([
            'listicle_id' => $listicle->id,
            'blockable_type' => AttractionBlock::class,
            'blockable_id' => $attractionBlockEn1->id,
            'order' => 0,
            'language' => 'en',
        ]);

        ContentBlock::create([
            'listicle_id' => $listicle->id,
            'blockable_type' => AttractionBlock::class,
            'blockable_id' => $attractionBlockEn2->id,
            'order' => 1,
            'language' => 'en',
        ]);

        // Verify different number of blocks per language
        $this->assertEquals(1, $listicle->contentBlocks()->where('language', 'de')->count());
        $this->assertEquals(2, $listicle->contentBlocks()->where('language', 'en')->count());
    }
}
