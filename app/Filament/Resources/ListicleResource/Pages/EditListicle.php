<?php

namespace App\Filament\Resources\ListicleResource\Pages;

use App\Filament\Resources\ListicleResource;
use App\Filament\Resources\ListicleResource\Actions\GenerateIntroAction;
use App\Models\AttractionBlock;
use App\Models\ContentBlock;
use App\Models\RelatedLinksBlock;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditListicle extends EditRecord
{
    protected static string $resource = ListicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GenerateIntroAction::make('de'),
            GenerateIntroAction::make('en'),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load German content blocks
        $contentBlocksDe = $this->record->contentBlocks()
            ->where('language', 'de')
            ->with('blockable')
            ->orderBy('order')
            ->get();

        $data['content_blocks_data_de'] = $contentBlocksDe->map(function ($block) {
            $blockData = ['block_type' => null];

            if ($block->blockable_type === AttractionBlock::class) {
                $blockData['block_type'] = 'location';
                $blockData['attraction_id'] = $block->blockable->attraction_id;
                $blockData['custom_intro'] = $block->blockable->custom_intro;
            } elseif ($block->blockable_type === RelatedLinksBlock::class) {
                $blockData['block_type'] = 'related_links';
                $blockData['title'] = $block->blockable->title;
                $blockData['links'] = $block->blockable->links;
            }

            return $blockData;
        })->toArray();

        // Load English content blocks
        $contentBlocksEn = $this->record->contentBlocks()
            ->where('language', 'en')
            ->with('blockable')
            ->orderBy('order')
            ->get();

        $data['content_blocks_data_en'] = $contentBlocksEn->map(function ($block) {
            $blockData = ['block_type' => null];

            if ($block->blockable_type === AttractionBlock::class) {
                $blockData['block_type'] = 'location';
                $blockData['attraction_id'] = $block->blockable->attraction_id;
                $blockData['custom_intro'] = $block->blockable->custom_intro;
            } elseif ($block->blockable_type === RelatedLinksBlock::class) {
                $blockData['block_type'] = 'related_links';
                $blockData['title'] = $block->blockable->title;
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
            // Create AttractionBlock
            $attractionBlock = AttractionBlock::create([
                'attraction_id' => $blockData['attraction_id'],
                'custom_intro' => $blockData['custom_intro'] ?? null,
            ]);

            // Create single ContentBlock with language
            ContentBlock::create([
                'listicle_id' => $this->record->id,
                'blockable_type' => AttractionBlock::class,
                'blockable_id' => $attractionBlock->id,
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
                'listicle_id' => $this->record->id,
                'blockable_type' => RelatedLinksBlock::class,
                'blockable_id' => $relatedLinksBlock->id,
                'order' => $index,
                'language' => $language,
            ]);
        }
    }
}
