<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class LocationBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'custom_intro',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function contentBlock(): MorphOne
    {
        return $this->morphOne(ContentBlock::class, 'blockable');
    }

    public function renderHtml(string $language = 'de'): string
    {
        $html = '';

        // Load location if not already loaded
        if (!$this->relationLoaded('location')) {
            $this->load('location');
        }

        $location = $this->location;

        if (!$location) {
            return '';
        }

        $html .= '<div class="content-page__location">';

        if ($this->custom_intro) {
            $html .= '<div class="content-page__location-intro">' . $this->custom_intro . '</div>';
        }

        // Use existing location widgets (still language-specific from Location model)
        $contactField = $language === 'de' ? 'contact_info_html' : 'en_contact_info_html';
        $ratingField = $language === 'de' ? 'rating_html' : 'en_rating_html';
        $openingHoursField = $language === 'de' ? 'opening_hours_html' : 'en_opening_hours_html';
        $accessibilityField = $language === 'de' ? 'accessibility_html' : 'en_accessibility_html';

        if ($location->{$contactField}) {
            $html .= $location->{$contactField};
        }

        if ($location->{$ratingField}) {
            $html .= $location->{$ratingField};
        }

        if ($location->{$openingHoursField}) {
            $html .= $location->{$openingHoursField};
        }

        if ($location->{$accessibilityField}) {
            $html .= $location->{$accessibilityField};
        }

        $html .= '</div>';

        return $html;
    }
}
