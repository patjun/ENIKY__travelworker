<?php

namespace App\Filament\Resources\ContentPageResource\Pages;

use App\Filament\Resources\ContentPageResource;
use App\Models\ContentBlock;
use App\Models\LocationBlock;
use App\Models\RelatedLinksBlock;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContentPage extends EditRecord
{
    protected static string $resource = ContentPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load all content blocks into the form
        $contentBlocks = $this->record->contentBlocks()
            ->where('language', 'de')
            ->with('blockable')
            ->orderBy('order')
            ->get();

        $data['content_blocks_data'] = $contentBlocks->map(function ($block) {
            $blockData = ['block_type' => null];

            if ($block->blockable_type === LocationBlock::class) {
                $blockData['block_type'] = 'location';
                $blockData['location_id'] = $block->blockable->location_id;
                $blockData['custom_intro_de'] = $block->blockable->custom_intro_de;
                $blockData['custom_intro_en'] = $block->blockable->custom_intro_en;
            } elseif ($block->blockable_type === RelatedLinksBlock::class) {
                $blockData['block_type'] = 'related_links';
                $blockData['title_de'] = $block->blockable->title_de;
                $blockData['title_en'] = $block->blockable->title_en;
                $blockData['links'] = $block->blockable->links;
            }

            return $blockData;
        })->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        // Delete all existing content blocks
        $this->deleteExistingBlocks();

        // Handle content blocks
        $this->handleContentBlocks();

        // Generate HTML widgets
        $this->record->generateWidgets();
    }

    protected function deleteExistingBlocks(): void
    {
        $existingBlocks = $this->record->contentBlocks()->get();

        foreach ($existingBlocks as $block) {
            $block->blockable?->delete();
            $block->delete();
        }
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
