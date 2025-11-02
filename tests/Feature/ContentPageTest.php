<?php

namespace Tests\Feature;

use App\Models\ContentBlock;
use App\Models\ContentPage;
use App\Models\Location;
use App\Models\RelatedLinksBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_content_page(): void
    {
        $contentPage = ContentPage::factory()->create();

        $this->assertDatabaseHas('content_pages', [
            'id' => $contentPage->id,
            'title_de' => $contentPage->title_de,
        ]);
    }

    public function test_can_attach_locations_to_content_page(): void
    {
        $contentPage = ContentPage::factory()->create();
        $location = Location::factory()->create();

        $contentPage->locations()->attach($location->id, [
            'order' => 0,
            'custom_intro_de' => 'Test intro',
        ]);

        $this->assertDatabaseHas('content_page_location', [
            'content_page_id' => $contentPage->id,
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

    public function test_can_create_content_block_with_related_links(): void
    {
        $contentPage = ContentPage::factory()->create();
        $relatedLinksBlock = RelatedLinksBlock::factory()->create();

        $contentBlock = ContentBlock::create([
            'content_page_id' => $contentPage->id,
            'blockable_type' => RelatedLinksBlock::class,
            'blockable_id' => $relatedLinksBlock->id,
            'order' => 0,
            'language' => 'de',
        ]);

        $this->assertDatabaseHas('content_blocks', [
            'id' => $contentBlock->id,
            'content_page_id' => $contentPage->id,
        ]);

        $this->assertEquals($relatedLinksBlock->id, $contentBlock->blockable_id);
    }

    public function test_generates_html_for_content_page(): void
    {
        $contentPage = ContentPage::factory()->create([
            'intro_de' => '<p>Test intro</p>',
        ]);

        $location = Location::factory()->create([
            'contact_info_html' => '<div>Contact info</div>',
        ]);

        $contentPage->locations()->attach($location->id, [
            'order' => 0,
            'custom_intro_de' => '<p>Custom intro</p>',
        ]);

        $contentPage->generateWidgets();

        $this->assertNotNull($contentPage->generated_html_de);
        $this->assertStringContainsString('Test intro', $contentPage->generated_html_de);
        $this->assertStringContainsString('Custom intro', $contentPage->generated_html_de);
    }

    public function test_related_links_block_renders_html(): void
    {
        $relatedLinksBlock = RelatedLinksBlock::factory()->create([
            'title_de' => 'Test Title',
            'links' => [
                [
                    'title_de' => 'Link 1',
                    'title_en' => 'Link 1',
                    'url' => 'https://example.com/link1',
                ],
            ],
        ]);

        $html = $relatedLinksBlock->renderHtml('de');

        $this->assertStringContainsString('Test Title', $html);
        $this->assertStringContainsString('Link 1', $html);
        $this->assertStringContainsString('https://example.com/link1', $html);
    }
}
