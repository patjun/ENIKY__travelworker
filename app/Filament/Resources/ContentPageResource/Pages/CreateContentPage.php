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
        // Handle German blocks
        $contentBlocksDataDe = $this->data['content_blocks_data_de'] ?? [];
        foreach (array_values($contentBlocksDataDe) as $index => $blockData) {
            $this->createBlock($blockData, $index, 'de');
        }

        // Handle English blocks
        $contentBlocksDataEn = $this->data['content_blocks_data_en'] ?? [];
        foreach (array_values($contentBlocksDataEn) as $index => $blockData) {
            $this->createBlock($blockData, $index, 'en');
        }
    }

    protected function createBlock(array $blockData, int $index, string $language): void
    {
        $blockType = $blockData['block_type'] ?? null;

        if ($blockType === 'location') {
            // Create LocationBlock
            $locationBlock = LocationBlock::create([
                'location_id' => $blockData['location_id'],
                'custom_intro' => $blockData['custom_intro'] ?? null,
            ]);

            // Create single ContentBlock with language
            ContentBlock::create([
                'content_page_id' => $this->record->id,
                'blockable_type' => LocationBlock::class,
                'blockable_id' => $locationBlock->id,
                'order' => $index,
                'language' => $language,
            ]);
        } elseif ($blockType === 'related_links') {
            // Create RelatedLinksBlock
            $relatedLinksBlock = RelatedLinksBlock::create([
                'title' => $blockData['title'] ?? ($language === 'de' ? 'Das könnte Dich auch interessieren' : 'You might also be interested in'),
                'links' => $blockData['links'] ?? [],
            ]);

            // Create single ContentBlock with language
            ContentBlock::create([
                'content_page_id' => $this->record->id,
                'blockable_type' => RelatedLinksBlock::class,
                'blockable_id' => $relatedLinksBlock->id,
                'order' => $index,
                'language' => $language,
            ]);
        }
    }
}
