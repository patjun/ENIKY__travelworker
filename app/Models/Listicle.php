<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'listicles';

    protected $fillable = [
        'title_de',
        'title_en',
        'slug_de',
        'slug_en',
        'intro_de',
        'intro_en',
        'meta_description_de',
        'meta_description_en',
        'generated_html_de',
        'generated_html_en',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function contentBlocks(): HasMany
    {
        return $this->hasMany(ContentBlock::class)->orderBy('order');
    }

    public function generateListicleHtml(string $language = 'de'): string
    {
        $html = '';

        // Add intro if present
        $introField = $language === 'de' ? 'intro_de' : 'intro_en';
        if ($this->{$introField}) {
            $html .= '<div class="content-listicle__intro">' . $this->{$introField} . '</div>';
        }

        // Add all content blocks (including locations) in order
        $blocks = $this->contentBlocks()
            ->where('language', $language)
            ->with('blockable')
            ->orderBy('order')
            ->get();

        foreach ($blocks as $block) {
            if ($block->blockable) {
                $html .= $block->blockable->renderHtml($language);
            }
        }

        return $html;
    }

    public function generateWidgets(): void
    {
        // Ensure relationships are loaded
        $this->loadMissing(['contentBlocks.blockable']);

        // Generate HTML for both languages
        $this->generated_html_de = $this->generateListicleHtml('de');
        $this->generated_html_en = $this->generateListicleHtml('en');

        // Save the generated HTML
        $this->saveQuietly();
    }
}
