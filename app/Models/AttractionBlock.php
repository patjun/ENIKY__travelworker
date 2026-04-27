<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class AttractionBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'attraction_id',
        'custom_intro',
    ];

    public function attraction(): BelongsTo
    {
        return $this->belongsTo(Attraction::class);
    }

    public function contentBlock(): MorphOne
    {
        return $this->morphOne(ContentBlock::class, 'blockable');
    }

    public function renderHtml(string $language = 'de'): string
    {
        $html = '';

        // Load attraction if not already loaded
        if (! $this->relationLoaded('attraction')) {
            $this->load('attraction');
        }

        $attraction = $this->attraction;

        if (! $attraction) {
            return '';
        }

        $html .= '<div class="content-page__attraction">';

        if ($this->custom_intro) {
            $html .= '<div class="content-page__attraction-intro">'.$this->custom_intro.'</div>';
        }

        // Use existing attraction widgets (still language-specific from Attraction model)
        $contactField = $language === 'de' ? 'contact_info_html' : 'en_contact_info_html';
        $ratingField = $language === 'de' ? 'rating_html' : 'en_rating_html';
        $openingHoursField = $language === 'de' ? 'opening_hours_html' : 'en_opening_hours_html';
        $accessibilityField = $language === 'de' ? 'accessibility_html' : 'en_accessibility_html';

        if ($attraction->{$contactField}) {
            $html .= $attraction->{$contactField};
        }

        if ($attraction->{$ratingField}) {
            $html .= $attraction->{$ratingField};
        }

        if ($attraction->{$openingHoursField}) {
            $html .= $attraction->{$openingHoursField};
        }

        if ($attraction->{$accessibilityField}) {
            $html .= $attraction->{$accessibilityField};
        }

        $html .= '</div>';

        return $html;
    }
}
