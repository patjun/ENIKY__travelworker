<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class RelatedLinksBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'links',
    ];

    protected $casts = [
        'links' => 'array',
    ];

    public function contentBlock(): MorphOne
    {
        return $this->morphOne(ContentBlock::class, 'blockable');
    }

    public function renderHtml(string $language = 'de'): string
    {
        $title = $this->title ?? ($language === 'de' ? 'Weitere Links' : 'Related Links');

        if (empty($this->links)) {
            return '';
        }

        $html = '<div class="related-links-block">';
        $html .= '<h3 class="related-links-block__title">' . htmlspecialchars($title) . '</h3>';
        $html .= '<ul class="related-links-block__list">';

        foreach ($this->links as $link) {
            $linkTitle = $link['title'] ?? '';
            $linkUrl = $link['url'] ?? '';

            if ($linkTitle && $linkUrl) {
                $html .= '<li class="related-links-block__item">';
                $html .= '<a href="' . htmlspecialchars($linkUrl) . '" class="related-links-block__link">';
                $html .= htmlspecialchars($linkTitle);
                $html .= '</a>';
                $html .= '</li>';
            }
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}
