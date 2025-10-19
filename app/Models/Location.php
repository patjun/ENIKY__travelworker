<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'city_id', 'name', 'street', 'zip', 'latitude', 'longitude',
        'cid', 'place_id', 'task_id', 'task_post_output', 'task_get_output',
        'location_code', 'language_code', 'business_data', 'last_dataforseo_update',
        'job_status', 'post_attempts', 'get_attempts', 'phone', 'email', 'website',
        'website_opening_hours', 'website_pricing',
        'description', 'category', 'rating_value', 'rating_votes_count',
        'opening_hours', 'accessibility', 'main_image_url', 'is_claimed',
        'price_level', 'additional_categories', 'opening_hours_html', 'structured_data',
        'contact_info_html', 'rating_html', 'accessibility_html', 'manual_opening_hours',
        'en_name', 'en_street', 'en_zip', 'en_phone', 'en_email', 'en_website',
        'en_website_opening_hours', 'en_website_pricing',
        'en_description', 'en_category', 'en_opening_hours', 'en_accessibility',
        'en_main_image_url', 'en_price_level', 'en_additional_categories',
        'en_opening_hours_html', 'en_structured_data', 'en_contact_info_html', 'en_rating_html', 'en_accessibility_html',
        'en_task_id', 'en_task_post_output', 'en_task_get_output', 'en_business_data',
        'en_last_dataforseo_update', 'en_job_status', 'en_post_attempts', 'en_get_attempts',
        'wp_de_last_sync', 'wp_de_id', 'wp_en_last_sync', 'wp_en_id',
    ];

    protected $casts = [
        'task_post_output' => 'array',
        'task_get_output' => 'array',
        'business_data' => 'array',
        'last_dataforseo_update' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'opening_hours' => 'array',
        'accessibility' => 'array',
        'additional_categories' => 'array',
        'is_claimed' => 'boolean',
        'manual_opening_hours' => 'array',
        'en_opening_hours' => 'array',
        'en_accessibility' => 'array',
        'en_additional_categories' => 'array',
        'en_task_post_output' => 'array',
        'en_task_get_output' => 'array',
        'en_business_data' => 'array',
        'en_last_dataforseo_update' => 'datetime',
        'wp_de_last_sync' => 'datetime',
        'wp_en_last_sync' => 'datetime',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function accessibilityAttributes(): BelongsToMany
    {
        return $this->belongsToMany(AccessibilityAttribute::class, 'location_accessibility_attribute');
    }

    public function generateWidgets()
    {
        // Transform manual opening hours to timetable format if available
        if (! empty($this->manual_opening_hours)) {
            $timetable = $this->transformManualOpeningHoursToTimetable($this->manual_opening_hours);
            $this->opening_hours = ['work_hours' => ['timetable' => $timetable]];
            $this->en_opening_hours = ['work_hours' => ['timetable' => $timetable]];
        }

        // Extract accessibility data from business_data if accessibility columns are empty
        if (empty($this->accessibility) && ! empty($this->business_data)) {
            $attributes = $this->business_data['items'][0]['attributes'] ?? null;
            if ($attributes) {
                $this->accessibility = $attributes;
            }
        }

        if (empty($this->en_accessibility) && ! empty($this->en_business_data)) {
            $enAttributes = $this->en_business_data['items'][0]['attributes'] ?? null;
            if ($enAttributes) {
                $this->en_accessibility = $enAttributes;
            }
        }

        // Generate all widget HTML
        $this->contact_info_html = $this->generateContactInfoHtml('de');
        $this->accessibility_html = $this->generateAccessibilityHtml('de');
        $this->rating_html = $this->generateRatingHtml('de');
        $this->opening_hours_html = $this->generateOpeningHoursHtml('de');
        $this->structured_data = $this->generateStructuredData('de');
        $this->en_contact_info_html = $this->generateContactInfoHtml('en');
        $this->en_accessibility_html = $this->generateAccessibilityHtml('en');
        $this->en_rating_html = $this->generateRatingHtml('en');
        $this->en_opening_hours_html = $this->generateOpeningHoursHtml('en');
        $this->en_structured_data = $this->generateStructuredData('en');
    }

    public function generateContactInfoHtml($language = 'de')
    {
        $name = $language === 'en' ? ($this->en_name ?: $this->name) : $this->name;
        $street = $language === 'en' ? ($this->en_street ?: $this->street) : $this->street;
        $zip = $language === 'en' ? ($this->en_zip ?: $this->zip) : $this->zip;

        // Load city relationship if not loaded
        if (! $this->relationLoaded('city')) {
            $this->load('city.country');
        }

        $cityModel = $this->city()->first();
        $city = $cityModel ? ($language === 'en' ? $cityModel->name_en : $cityModel->name_de) : null;
        $country = $cityModel && $cityModel->country ? ($language === 'en' ? $cityModel->country->name_en : $cityModel->country->name_de) : null;

        $website = $language === 'en' ? ($this->en_website ?: $this->website) : $this->website;
        $phone = $language === 'en' ? ($this->en_phone ?: $this->phone) : $this->phone;
        $email = $language === 'en' ? ($this->en_email ?: $this->email) : $this->email;

        $html = "<div class=\"widget contact-info\">\n";

        // Header with business name
        if ($name) {
            $html .= "  <div class=\"header\">\n";
            $html .= "    <h3 class=\"title\">{$name}</h3>\n";
            $html .= "  </div>\n";
        }

        $html .= "  <div class=\"contact widget-content\">\n";

        // Address section
        if ($street || $city || $country || $zip) {
            $html .= "    <div class=\"item\">\n";
            $html .= "      <div class=\"icon\">📍</div>\n";
            $html .= "      <div class=\"info\">\n";

            $addressParts = [];
            if ($street) {
                $addressParts[] = $street;
            }
            if ($zip && $city) {
                $addressParts[] = $zip.' '.$city;
            } elseif ($city) {
                $addressParts[] = $city;
            }
            if ($country) {
                $addressParts[] = $country;
            }

            foreach ($addressParts as $part) {
                $html .= "        <div class=\"line\">{$part}</div>\n";
            }

            $html .= "      </div>\n";
            $html .= "    </div>\n";
        }

        // Website section
        if ($website) {
            $websiteText = $language === 'en' ? 'Visit Website' : 'Website besuchen';
            $html .= "    <div class=\"item\">\n";
            $html .= "      <div class=\"icon\">🌐</div>\n";
            $html .= "      <div class=\"info\">\n";
            $html .= "        <a href=\"{$website}\" target=\"_blank\" rel=\"noopener\" class=\"link\">{$websiteText}</a>\n";
            $html .= "      </div>\n";
            $html .= "    </div>\n";
        }

        // E-Mail section
        if ($email) {
            $html .= "    <div class=\"item\">\n";
            $html .= "      <div class=\"icon\">✉️</div>\n";
            $html .= "      <div class=\"info\">\n";
            $html .= "        <a href=\"mailto:{$email}\" target=\"_blank\" rel=\"noopener\" class=\"link\">{$email}</a>\n";
            $html .= "      </div>\n";
            $html .= "    </div>\n";
        }

        // Phone section
        if ($phone) {
            $html .= "    <div class=\"item\">\n";
            $html .= "      <div class=\"icon\">📞</div>\n";
            $html .= "      <div class=\"info\">\n";
            $html .= "        <a href=\"tel:{$phone}\" class=\"contact-link\">{$phone}</a>\n";
            $html .= "      </div>\n";
            $html .= "    </div>\n";
        }

        $html .= "  </div>\n";
        $html .= '</div>';

        return $html;
    }

    public function generateAccessibilityHtml($language = 'de')
    {
        // Load accessibility attributes from relationship
        $attributes = $this->accessibilityAttributes;

        // Return null if no accessibility attributes
        if ($attributes->isEmpty()) {
            return null;
        }

        // Set up translations
        $titleText = $language === 'en' ? 'Accessibility' : 'Barrierefreiheit';

        // Start building HTML
        $output = '<div class="widget accessibility">'."\n";
        $output .= '  <div class="header">'."\n";
        $output .= '    <h3 class="title">'.$titleText.'</h3>'."\n";
        $output .= '  </div>'."\n";
        $output .= '  <div class="widget-content accessibility">'."\n";

        // Add all accessibility features
        foreach ($attributes as $attribute) {
            // Get the name in the correct language
            $label = $language === 'en' ? $attribute->name_en : $attribute->name_de;

            $output .= '    <div class="item available">'."\n";
            $output .= '      <span class="status yes">✓</span>'."\n";
            $output .= '      <span class="label">'.htmlspecialchars($label).'</span>'."\n";
            $output .= '    </div>'."\n";
        }

        $output .= '  </div>'."\n";
        $output .= '</div>';

        return $output;
    }

    public function generateRatingHtml($language = 'de')
    {
        if (! $this->rating_value || ! $this->rating_votes_count) {
            return null;
        }

        $ratingText = $language === 'en' ? 'Our Rating' : 'Unsere Bewertung';
        $reviewsText = $language === 'en' ? 'Reviews' : 'Bewertungen';
        $outOfText = $language === 'en' ? 'out of' : 'von';

        $html = "<div class=\"widget rating\">\n";

        // Header
        $html .= "  <div class=\"header\">\n";
        $html .= "    <h3 class=\"title\">{$ratingText}</h3>\n";
        $html .= "  </div>\n";

        $html .= "  <div class=\"widget-content rating\">\n";

        // Main rating display
        $html .= "    <div class=\"score\">\n";
        $html .= "      <div class=\"number\">{$this->rating_value}</div>\n";
        $html .= "      <div class=\"details\">\n";

        // Star display
        $html .= "        <div class=\"stars\">\n";
        $fullStars = floor($this->rating_value);
        $hasHalfStar = ($this->rating_value - $fullStars) >= 0.5;

        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $fullStars) {
                $html .= "          <span class=\"star star-full\">★</span>\n";
            } elseif ($i == $fullStars + 1 && $hasHalfStar) {
                $html .= "          <span class=\"star star-half\">☆</span>\n";
            } else {
                $html .= "          <span class=\"star star-empty\">☆</span>\n";
            }
        }
        $html .= "        </div>\n";

        // Rating text
        $html .= "        <div class=\"text\">\n";
        $html .= "          <span class=\"out-of\">{$outOfText} 5</span>\n";
        $html .= "        </div>\n";

        $html .= "      </div>\n";
        $html .= "    </div>\n";

        // Review count
        $html .= "    <div class=\"reviews\">\n";
        $html .= '      <span class="count">'.number_format($this->rating_votes_count)."</span>\n";
        $html .= "      <span class=\"label\">{$reviewsText}</span>\n";
        $html .= "    </div>\n";

        $html .= "  </div>\n";
        $html .= '</div>';

        return $html;
    }

    public function generateOpeningHoursHtml($language = 'de')
    {
        $openingHours = $language === 'en' ? $this->en_opening_hours : $this->opening_hours;

        if (! $openingHours || ! isset($openingHours['work_hours']['timetable'])) {
            return null;
        }

        $timetable = $openingHours['work_hours']['timetable'];
        $dayNames = $language === 'en' ? [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ] : [
            'monday' => 'Montag',
            'tuesday' => 'Dienstag',
            'wednesday' => 'Mittwoch',
            'thursday' => 'Donnerstag',
            'friday' => 'Freitag',
            'saturday' => 'Samstag',
            'sunday' => 'Sonntag',
        ];

        $closedText = $language === 'en' ? 'Closed' : 'Geschlossen';
        $openingHoursText = $language === 'en' ? 'Opening Hours' : 'Öffnungszeiten';

        $html = "<div class=\"widget\">\n";
        $html .= "  <div class=\"header\">\n";
        $html .= "    <h3 class=\"title\">{$openingHoursText}</h3>\n";
        $html .= "  </div>\n";

        $dayOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        $html .= " <div class=\"opening-hours widget-content\">\n";
        foreach ($dayOrder as $day) {
            $dayLabel = $dayNames[$day];
            $html .= "    <div class=\"day\">\n";

            if (isset($timetable[$day]) && ! empty($timetable[$day])) {
                foreach ($timetable[$day] as $index => $hours) {
                    if ($language === 'en') {
                        // 12-hour format for English
                        $openTime = $this->formatTime12Hour($hours['open']['hour'], $hours['open']['minute']);
                        $closeTime = $this->formatTime12Hour($hours['close']['hour'], $hours['close']['minute']);
                    } else {
                        // 24-hour format for German
                        $openTime = sprintf('%02d:%02d', $hours['open']['hour'], $hours['open']['minute']);
                        $closeTime = sprintf('%02d:%02d', $hours['close']['hour'], $hours['close']['minute']);
                    }

                    if ($index === 0) {
                        $html .= "      <div class=\"timeline first\"><span class=\"name\">{$dayLabel}</span>\n";
                        $html .= "      <span class=\"time\">{$openTime} - {$closeTime}</span></div>\n";
                    } else {
                        $html .= "      <div class=\"timeline\"><span class=\"name\"></span>\n";
                        $html .= "      <span class=\"time\">{$openTime} - {$closeTime}</span></div>\n";
                    }
                }
            } else {
                $html .= "      <div class=\"timeline\"><span class=\"name\">{$dayLabel}</span>\n";
                $html .= "      <span class=\"closed\">{$closedText}</span></div>\n";
            }

            $html .= "    </div>\n";
        }

        $html .= '  </div>';
        $html .= '</div>';

        return $html;
    }

    public function generateStructuredData($language = 'de')
    {
        $name = $language === 'en' ? ($this->en_name ?: $this->name) : $this->name;
        $street = $language === 'en' ? ($this->en_street ?: $this->street) : $this->street;
        $zip = $language === 'en' ? ($this->en_zip ?: $this->zip) : $this->zip;

        // Load city relationship if not loaded
        if (! $this->relationLoaded('city')) {
            $this->load('city.country');
        }

        $cityModel = $this->city()->first();
        $city = $cityModel ? ($language === 'en' ? $cityModel->name_en : $cityModel->name_de) : null;
        $country = $cityModel && $cityModel->country ? ($language === 'en' ? $cityModel->country->name_en : $cityModel->country->name_de) : null;

        $phone = $language === 'en' ? ($this->en_phone ?: $this->phone) : $this->phone;
        $email = $language === 'en' ? ($this->en_email ?: $this->email) : $this->email;
        $website = $language === 'en' ? ($this->en_website ?: $this->website) : $this->website;
        $description = $language === 'en' ? ($this->en_description ?: $this->description) : $this->description;
        $category = $language === 'en' ? ($this->en_category ?: $this->category) : $this->category;
        $openingHours = $language === 'en' ? $this->en_opening_hours : $this->opening_hours;
        $imageUrl = $language === 'en' ? ($this->en_main_image_url ?: $this->main_image_url) : $this->main_image_url;
        $priceLevel = $language === 'en' ? ($this->en_price_level ?: $this->price_level) : $this->price_level;

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $name,
        ];

        if ($street || $city || $country || $zip) {
            $structuredData['address'] = [
                '@type' => 'PostalAddress',
            ];
            if ($street) {
                $structuredData['address']['streetAddress'] = $street;
            }
            if ($city) {
                $structuredData['address']['addressLocality'] = $city;
            }
            if ($country) {
                $structuredData['address']['addressCountry'] = $country;
            }
            if ($zip) {
                $structuredData['address']['postalCode'] = $zip;
            }
        }

        if ($phone) {
            $structuredData['telephone'] = $phone;
        }

        if ($email) {
            $structuredData['email'] = $email;
        }

        if ($website) {
            $structuredData['url'] = $website;
        }

        if ($this->rating_value && $this->rating_votes_count) {
            $structuredData['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $this->rating_value,
                'reviewCount' => (int) $this->rating_votes_count,
                'bestRating' => 5,
            ];
        }

        if ($priceLevel) {
            $structuredData['priceRange'] = $priceLevel;
        }

        if ($openingHours && isset($openingHours['work_hours']['timetable'])) {
            $structuredData['openingHoursSpecification'] = $this->generateOpeningHoursSpecification($openingHours['work_hours']['timetable']);
        }

        $jsonString = json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return "<script type=\"application/ld+json\">\n{$jsonString}\n</script>";
    }

    private function generateOpeningHoursSpecification($timetable)
    {
        $dayMapping = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ];

        $specifications = [];
        $groupedDays = [];

        // Collect all time slots for each day
        foreach ($timetable as $day => $timeSlots) {
            if (! empty($timeSlots)) {
                foreach ($timeSlots as $slot) {
                    $timeKey = sprintf('%02d:%02d-%02d:%02d',
                        $slot['open']['hour'],
                        $slot['open']['minute'],
                        $slot['close']['hour'],
                        $slot['close']['minute']
                    );
                    $groupedDays[$timeKey][] = $dayMapping[$day];
                }
            }
        }

        // Create specifications from grouped days
        foreach ($groupedDays as $timeRange => $days) {
            [$openTime, $closeTime] = explode('-', $timeRange);

            $specifications[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => count($days) === 1 ? $days[0] : $days,
                'opens' => $openTime,
                'closes' => $closeTime,
            ];
        }

        return $specifications;
    }

    private function formatTime12Hour($hour, $minute)
    {
        $ampm = $hour >= 12 ? 'PM' : 'AM';
        $displayHour = $hour == 0 ? 12 : ($hour > 12 ? $hour - 12 : $hour);

        return sprintf('%d:%02d %s', $displayHour, $minute, $ampm);
    }

    private function transformManualOpeningHoursToTimetable(array $manualHours): array
    {
        $timetable = [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => [],
        ];

        foreach ($manualHours as $slot) {
            if (empty($slot['days']) || empty($slot['open_time']) || empty($slot['close_time'])) {
                continue;
            }

            // Parse open time
            $openParts = explode(':', $slot['open_time']);
            $openHour = (int) $openParts[0];
            $openMinute = (int) ($openParts[1] ?? 0);

            // Parse close time
            $closeParts = explode(':', $slot['close_time']);
            $closeHour = (int) $closeParts[0];
            $closeMinute = (int) ($closeParts[1] ?? 0);

            // Add this time slot to each selected day
            foreach ($slot['days'] as $day) {
                $timetable[$day][] = [
                    'open' => [
                        'hour' => $openHour,
                        'minute' => $openMinute,
                    ],
                    'close' => [
                        'hour' => $closeHour,
                        'minute' => $closeMinute,
                    ],
                ];
            }
        }

        // Sort each day's time slots by opening time
        foreach ($timetable as $day => $slots) {
            if (! empty($slots)) {
                usort($timetable[$day], function ($a, $b) {
                    // Compare hours first
                    if ($a['open']['hour'] !== $b['open']['hour']) {
                        return $a['open']['hour'] - $b['open']['hour'];
                    }

                    // If hours are equal, compare minutes
                    return $a['open']['minute'] - $b['open']['minute'];
                });
            }
        }

        return $timetable;
    }
}
