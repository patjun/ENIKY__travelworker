<?php

namespace App\Filament\Resources\ContentPageResource\Pages;

use App\Filament\Resources\ContentPageResource;
use App\Models\ContentBlock;
use App\Models\LocationBlock;
use App\Models\RelatedLinksBlock;
use Filament\Resources\Pages\CreateRecord;

class CreateContentPage extends CreateRecord
{
    protected static string $resource = ContentPageResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function afterCreate(): void
    {
        // Handle content blocks
        $this->handleContentBlocks();

        // Generate HTML widgets
        $this->record->generateWidgets();
    }

    protected function handleContentBlocks(): void
    {
        $contentBlocksData = $this->data['content_blocks_data'] ?? [];

        foreach (array_values($contentBlocksData) as $index => $blockData) {
            $blockType = $blockData['block_type'] ?? null;

            if ($blockType === 'location') {
                // Create LocationBlock
                $locationBlock = LocationBlock::create([
                    'location_id' => $blockData['location_id'],
                    'custom_intro_de' => $blockData['custom_intro_de'] ?? null,
                    'custom_intro_en' => $blockData['custom_intro_en'] ?? null,
                ]);

                // Create ContentBlocks for both languages
                ContentBlock::create([
                    'content_page_id' => $this->record->id,
                    'blockable_type' => LocationBlock::class,
                    'blockable_id' => $locationBlock->id,
                    'order' => $index,
                    'language' => 'de',
                ]);

                ContentBlock::create([
                    'content_page_id' => $this->record->id,
                    'blockable_type' => LocationBlock::class,
                    'blockable_id' => $locationBlock->id,
                    'order' => $index,
                    'language' => 'en',
                ]);
            } elseif ($blockType === 'related_links') {
                // Create RelatedLinksBlock
                $relatedLinksBlock = RelatedLinksBlock::create([
                    'title_de' => $blockData['title_de'] ?? 'Das könnte Dich auch interessieren',
                    'title_en' => $blockData['title_en'] ?? 'You might also be interested in',
                    'links' => $blockData['links'] ?? [],
                ]);

                // Create ContentBlocks for both languages
                ContentBlock::create([
                    'content_page_id' => $this->record->id,
                    'blockable_type' => RelatedLinksBlock::class,
                    'blockable_id' => $relatedLinksBlock->id,
                    'order' => $index,
                    'language' => 'de',
                ]);

                ContentBlock::create([
                    'content_page_id' => $this->record->id,
                    'blockable_type' => RelatedLinksBlock::class,
                    'blockable_id' => $relatedLinksBlock->id,
                    'order' => $index,
                    'language' => 'en',
                ]);
            }
        }
    }
}
