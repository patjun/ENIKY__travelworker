<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attraction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'city_id', 'name', 'street', 'zip', 'latitude', 'longitude',
        'cid', 'place_id', 'task_id', 'task_post_output', 'task_get_output',
        'location_code', 'language_code', 'business_data', 'last_dataforseo_update',
        'job_status', 'post_attempts', 'get_attempts', 'email', 'website',
        'website_opening_hours', 'website_pricing',
        'description', 'category', 'rating_value', 'rating_votes_count', 'rating_text', 'en_rating_text',
        'opening_hours', 'accessibility', 'main_image_url', 'is_claimed',
        'price_level', 'additional_categories', 'opening_hours_html', 'structured_data',
        'contact_info_html', 'rating_html', 'accessibility_html', 'manual_opening_hours',
        'en_name', 'en_website',
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
        'contact_info_changed_at' => 'datetime',
        'rating_changed_at' => 'datetime',
        'opening_hours_changed_at' => 'datetime',
        'accessibility_changed_at' => 'datetime',
        'structured_data_changed_at' => 'datetime',
        'en_contact_info_changed_at' => 'datetime',
        'en_rating_changed_at' => 'datetime',
        'en_opening_hours_changed_at' => 'datetime',
        'en_accessibility_changed_at' => 'datetime',
        'en_structured_data_changed_at' => 'datetime',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function accessibilityAttributes(): BelongsToMany
    {
        return $this->belongsToMany(AccessibilityAttribute::class, 'attraction_accessibility_attribute');
    }

    /**
     * Map WordPress API fields to the HTML column, its change timestamp column
     * and the generator method for the given language.
     *
     * @return array<string, array{html: string, changed_at: string, generator: string}>
     */
    public static function widgetMap(string $language): array
    {
        $prefix = $language === 'en' ? 'en_' : '';

        return [
            'widget_contact' => ['html' => "{$prefix}contact_info_html", 'changed_at' => "{$prefix}contact_info_changed_at", 'generator' => 'generateContactInfoHtml'],
            'widget_rating' => ['html' => "{$prefix}rating_html", 'changed_at' => "{$prefix}rating_changed_at", 'generator' => 'generateRatingHtml'],
            'widget_opening_hours' => ['html' => "{$prefix}opening_hours_html", 'changed_at' => "{$prefix}opening_hours_changed_at", 'generator' => 'generateOpeningHoursHtml'],
            'widget_accessibility' => ['html' => "{$prefix}accessibility_html", 'changed_at' => "{$prefix}accessibility_changed_at", 'generator' => 'generateAccessibilityHtml'],
            'json_ld' => ['html' => "{$prefix}structured_data", 'changed_at' => "{$prefix}structured_data_changed_at", 'generator' => 'generateStructuredData'],
        ];
    }

    public function generateWidgets(): void
    {
        // Ensure relationships are loaded
        $this->loadMissing(['city.country', 'accessibilityAttributes']);

        // Transform manual opening hours to timetable format if available
        if (! empty($this->manual_opening_hours)) {
            // Store seasons structure for later use in HTML generation
            $this->opening_hours = ['seasons' => $this->manual_opening_hours];
            $this->en_opening_hours = ['seasons' => $this->manual_opening_hours];
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

        foreach (['de', 'en'] as $language) {
            foreach (self::widgetMap($language) as $definition) {
                $this->assignWidgetHtml(
                    $definition['html'],
                    $definition['changed_at'],
                    (string) $this->{$definition['generator']}($language)
                );
            }
        }
    }

    /**
     * Collapse whitespace so purely cosmetic differences are not treated as changes.
     */
    public static function normalizeHtml(string $html): string
    {
        $html = (string) preg_replace('/>\s+</', '><', $html);

        return trim((string) preg_replace('/\s+/', ' ', $html));
    }

    /**
     * Store generated widget HTML and stamp the change time when the content differs.
     */
    private function assignWidgetHtml(string $htmlColumn, string $changedAtColumn, string $html): void
    {
        if (self::normalizeHtml((string) $this->{$htmlColumn}) !== self::normalizeHtml($html)) {
            $this->{$changedAtColumn} = now();
        }

        $this->{$htmlColumn} = $html;
    }

    public function generateContactInfoHtml($language = 'de')
    {
        $isEnglish = $language === 'en';
        $name = $isEnglish ? ($this->en_name ?: $this->name) : $this->name;

        $cityModel = $this->city()->first();
        $city = $cityModel ? ($isEnglish ? $cityModel->name_en : $cityModel->name_de) : null;
        $country = $cityModel && $cityModel->country ? ($isEnglish ? $cityModel->country->name_en : $cityModel->country->name_de) : null;

        $website = $isEnglish ? ($this->en_website ?: $this->website) : $this->website;

        $addressLines = array_filter([
            $this->street,
            trim(($this->zip ?? '').' '.($city ?? '')),
            $country,
        ]);

        $rows = '';

        if ($addressLines !== []) {
            $rows .= $this->renderWidgetRow(
                $isEnglish ? 'Address' : 'Adresse',
                implode('<br>', array_map('e', $addressLines))
            );
        }

        if ($website) {
            $host = preg_replace('/^www\./', '', (string) parse_url($website, PHP_URL_HOST)) ?: $website;
            $rows .= $this->renderWidgetRow(
                'Website',
                '<a class="am-link" href="'.e($website).'" target="_blank" rel="noopener">'.e($host).' ↗</a>'
            );
        }

        if ($this->email) {
            $rows .= $this->renderWidgetRow(
                $isEnglish ? 'Email' : 'E-Mail',
                '<a class="am-link am-break" href="mailto:'.e($this->email).'">'.e($this->email).'</a>'
            );
        }

        $html = $this->renderWidgetHead('contact', $isEnglish ? 'Contact' : 'Kontakt', (string) $name);

        return $html."  <dl class=\"am-rows\">\n{$rows}  </dl>\n</section>";
    }

    public function generateAccessibilityHtml($language = 'de')
    {
        $attributes = $this->accessibilityAttributes;

        if ($attributes->isEmpty()) {
            return null;
        }

        $isEnglish = $language === 'en';

        $html = $this->renderWidgetHead(
            'accessibility',
            $isEnglish ? 'Accessibility' : 'Barrierefreiheit',
            $isEnglish ? 'Easy to access?' : 'Gut zugänglich?'
        );
        $html .= "  <ul class=\"am-checks\">\n";

        foreach ($attributes as $attribute) {
            $label = $isEnglish ? $attribute->name_en : $attribute->name_de;

            $html .= '    <li class="am-check am-ok"><span class="am-mark" aria-hidden="true">✓</span>'
                .'<span class="am-check-label">'.e($label)."</span></li>\n";
        }

        return $html."  </ul>\n</section>";
    }

    public function generateRatingHtml($language = 'de')
    {
        if (! $this->rating_value || ! $this->rating_votes_count) {
            return null;
        }

        $isEnglish = $language === 'en';
        $rating = (float) $this->rating_value;
        $ratingText = $isEnglish ? ($this->en_rating_text ?: $this->rating_text) : $this->rating_text;

        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars .= $i <= round($rating)
                ? '<span class="am-star am-star-full">★</span>'
                : '<span class="am-star am-star-empty">★</span>';
        }

        $html = $this->renderWidgetHead(
            'rating',
            $isEnglish ? 'Our rating' : 'Unsere Bewertung',
            $isEnglish ? 'Worth a visit?' : 'Lohnt sich der Besuch?'
        );
        $html .= "  <div class=\"am-score\">\n";
        $html .= '    <span class="am-number">'.number_format($rating, 1, $isEnglish ? '.' : ',', '')."</span>\n";
        $html .= "    <div class=\"am-score-details\">\n";
        $html .= "      <span class=\"am-stars\" role=\"img\" aria-label=\"{$rating} / 5\">{$stars}</span>\n";
        $html .= '      <span class="am-out-of">'.($isEnglish ? 'out of 5 points' : 'von 5 Punkten')."</span>\n";
        $html .= "    </div>\n";
        $html .= "  </div>\n";

        if ($ratingText) {
            $html .= '  <p class="am-text">'.e($ratingText)."</p>\n";
        }

        return $html.'</section>';
    }

    public function generateOpeningHoursHtml($language = 'de')
    {
        $openingHours = $language === 'en' ? $this->en_opening_hours : $this->opening_hours;

        if (! $openingHours || empty($openingHours['seasons'])) {
            return null;
        }

        $isEnglish = $language === 'en';

        $dayNames = $isEnglish ? [
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

        $closedText = $isEnglish ? 'closed' : 'geschlossen';
        $todayText = $isEnglish ? 'Today' : 'Heute';

        $sortedSeasons = $this->sortSeasonsByDate($openingHours['seasons']);
        $activeSeason = $this->getActiveOrNextSeason($sortedSeasons);
        $timezone = $this->resolveTimezone();

        $html = $this->renderWidgetHead(
            'hours',
            $isEnglish ? 'Opening hours' : 'Öffnungszeiten',
            $isEnglish ? 'When is it open?' : 'Wann geöffnet?',
            ' data-today-label="'.e($todayText).'"'.($timezone ? ' data-timezone="'.e($timezone).'"' : '')
        );

        foreach ($sortedSeasons as $season) {
            $isActiveSeason = $activeSeason &&
                ($season['is_year_round'] ?? false) === ($activeSeason['is_year_round'] ?? false) &&
                ($season['start_date'] ?? null) === ($activeSeason['start_date'] ?? null) &&
                ($season['end_date'] ?? null) === ($activeSeason['end_date'] ?? null);

            if ($season['is_year_round'] ?? false) {
                $seasonTitle = $season['name'] ?: ($isEnglish ? 'Year-round' : 'Ganzjährig');
            } else {
                $dateRange = $this->formatDateRange($season['start_date'] ?? null, $season['end_date'] ?? null, $language);
                $seasonTitle = $season['name']
                    ? $season['name'].($dateRange ? ' ('.$dateRange.')' : '')
                    : $dateRange;
            }

            $timetable = $this->transformManualOpeningHoursToTimetable($season['time_slots'] ?? []);

            $html .= '  <details class="am-season"'.($isActiveSeason ? ' open' : '').">\n";
            $html .= '    <summary class="am-season-toggle"><span>'.e($seasonTitle)."</span></summary>\n";
            $html .= "    <div class=\"am-days\">\n";

            foreach ($dayNames as $day => $dayLabel) {
                $time = empty($timetable[$day])
                    ? '<span class="am-time am-closed">'.$closedText.'</span>'
                    : '<span class="am-time">'.e($this->formatOpeningSlots($timetable[$day], $isEnglish)).'</span>';

                $html .= "      <div class=\"am-day\" data-day=\"{$day}\"><span class=\"am-day-name\">{$dayLabel}</span>{$time}</div>\n";
            }

            $html .= "    </div>\n";
            $html .= "  </details>\n";
        }

        return $html.'</section>';
    }

    /**
     * Opens a widget card with its mono label and heading.
     */
    private function renderWidgetHead(string $type, string $label, string $title, string $attributes = ''): string
    {
        return "<section class=\"am-widget am-{$type}\"{$attributes}>\n"
            .'  <div class="am-label">'.e($label)."</div>\n"
            .'  <h3 class="am-title">'.e($title)."</h3>\n";
    }

    private function renderWidgetRow(string $label, string $valueHtml): string
    {
        return '    <div class="am-row"><dt>'.e($label).'</dt><dd>'.$valueHtml."</dd></div>\n";
    }

    /**
     * Formats the slots of one day, e.g. "9:30–12, 14–18 Uhr" or "9:30 am–12 pm, 2–6 pm".
     *
     * @param  array<int, array{open: array{hour: int, minute: int}, close: array{hour: int, minute: int}}>  $slots
     */
    private function formatOpeningSlots(array $slots, bool $isEnglish): string
    {
        $ranges = array_map(function (array $slot) use ($isEnglish): string {
            if ($isEnglish) {
                return $this->formatTime12Hour($slot['open']['hour'], $slot['open']['minute'])
                    .'–'.$this->formatTime12Hour($slot['close']['hour'], $slot['close']['minute']);
            }

            return $this->formatTime24Hour($slot['open']['hour'], $slot['open']['minute'])
                .'–'.$this->formatTime24Hour($slot['close']['hour'], $slot['close']['minute']);
        }, $slots);

        return implode(', ', $ranges).($isEnglish ? '' : ' Uhr');
    }

    private function formatTime24Hour(int $hour, int $minute): string
    {
        return $minute === 0 ? (string) $hour : sprintf('%d:%02d', $hour, $minute);
    }

    /**
     * IANA timezone of the attraction, only when its country has exactly one.
     */
    private function resolveTimezone(): ?string
    {
        $code = $this->city()->first()?->country?->code;

        if (! $code) {
            return null;
        }

        $zones = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, strtoupper($code));

        return count($zones) === 1 ? $zones[0] : null;
    }

    public function generateStructuredData($language = 'de')
    {
        $name = $language === 'en' ? ($this->en_name ?: $this->name) : $this->name;
        $street = $this->street;
        $zip = $this->zip;

        // Load city relationship if not loaded
        if (! $this->relationLoaded('city')) {
            $this->load('city.country');
        }

        $cityModel = $this->city()->first();
        $city = $cityModel ? ($language === 'en' ? $cityModel->name_en : $cityModel->name_de) : null;
        $country = $cityModel && $cityModel->country ? ($language === 'en' ? $cityModel->country->name_en : $cityModel->country->name_de) : null;

        $email = $this->email;
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

        if ($openingHours && isset($openingHours['seasons'])) {
            $structuredData['openingHoursSpecification'] = $this->generateOpeningHoursSpecification($openingHours);
        } elseif ($openingHours && isset($openingHours['work_hours']['timetable'])) {
            $structuredData['openingHoursSpecification'] = $this->generateOpeningHoursSpecification($openingHours['work_hours']['timetable']);
        }

        $jsonString = json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);

        return "<script type=\"application/ld+json\">\n{$jsonString}\n</script>";
    }

    private function generateOpeningHoursSpecification($openingHours)
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

        // Check if we have the new seasons structure
        if (isset($openingHours['seasons'])) {
            $seasons = $openingHours['seasons'];

            foreach ($seasons as $season) {
                $timeSlots = $season['time_slots'] ?? [];
                $timetable = $this->transformManualOpeningHoursToTimetable($timeSlots);

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

                    $spec = [
                        '@type' => 'OpeningHoursSpecification',
                        'dayOfWeek' => count($days) === 1 ? $days[0] : $days,
                        'opens' => $openTime,
                        'closes' => $closeTime,
                    ];

                    // Add validFrom and validThrough for seasonal hours
                    if (! ($season['is_year_round'] ?? false)) {
                        if (! empty($season['start_date'])) {
                            $spec['validFrom'] = '--'.$season['start_date'];
                        }
                        if (! empty($season['end_date'])) {
                            $spec['validThrough'] = '--'.$season['end_date'];
                        }
                    }

                    $specifications[] = $spec;
                }
            }
        } else {
            // Legacy support for old timetable structure
            $timetable = $openingHours['work_hours']['timetable'] ?? [];
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

    /**
     * Get the current active season based on today's date
     */
    private function getCurrentSeason(?array $seasons = null): ?array
    {
        if ($seasons === null) {
            $seasons = $this->manual_opening_hours ?? [];
        }

        if (empty($seasons)) {
            return null;
        }

        $today = now();
        $todayMd = $today->format('m-d');

        foreach ($seasons as $season) {
            // Year-round seasons are always active
            if ($season['is_year_round'] ?? false) {
                return $season;
            }

            // Check if today falls within the season's date range
            if (! empty($season['start_date']) && ! empty($season['end_date'])) {
                if ($this->isDateInRange($todayMd, $season['start_date'], $season['end_date'])) {
                    return $season;
                }
            }
        }

        // If no season matches, return year-round season if exists, otherwise first season
        foreach ($seasons as $season) {
            if ($season['is_year_round'] ?? false) {
                return $season;
            }
        }

        return $seasons[0] ?? null;
    }

    /**
     * Get the season to display (current or next upcoming)
     */
    private function getActiveOrNextSeason(?array $seasons = null): ?array
    {
        if ($seasons === null) {
            $seasons = $this->manual_opening_hours ?? [];
        }

        if (empty($seasons)) {
            return null;
        }

        // First, check if there's a current active season
        $currentSeason = $this->getCurrentSeason($seasons);

        // If there's a year-round season or currently active season, return it
        foreach ($seasons as $season) {
            if ($season['is_year_round'] ?? false) {
                return $season;
            }
        }

        if ($currentSeason !== null && ! ($currentSeason['is_year_round'] ?? false)) {
            // Check if it's actually in range
            $today = now();
            $todayMd = $today->format('m-d');
            if (! empty($currentSeason['start_date']) && ! empty($currentSeason['end_date'])) {
                if ($this->isDateInRange($todayMd, $currentSeason['start_date'], $currentSeason['end_date'])) {
                    return $currentSeason;
                }
            }
        }

        // No current season, find the next upcoming one
        $today = now();
        $todayMd = $today->format('m-d');

        $nextSeason = null;
        $minDaysUntilStart = PHP_INT_MAX;

        foreach ($seasons as $season) {
            if ($season['is_year_round'] ?? false) {
                continue; // Already checked above
            }

            if (empty($season['start_date'])) {
                continue;
            }

            $daysUntilStart = $this->calculateDaysUntilDate($todayMd, $season['start_date']);

            if ($daysUntilStart > 0 && $daysUntilStart < $minDaysUntilStart) {
                $minDaysUntilStart = $daysUntilStart;
                $nextSeason = $season;
            }
        }

        // If we found a next season, return it
        if ($nextSeason !== null) {
            return $nextSeason;
        }

        // Otherwise, return the first season
        return $seasons[0] ?? null;
    }

    /**
     * Calculate days until a target date (handles year wrap-around)
     */
    private function calculateDaysUntilDate(string $fromDate, string $toDate): int
    {
        try {
            $currentYear = now()->year;

            // Parse dates with current year
            $from = Carbon::createFromFormat('m-d', $fromDate)->year($currentYear);
            $to = Carbon::createFromFormat('m-d', $toDate)->year($currentYear);

            $diff = $from->diffInDays($to, false);

            // If the target date is in the past this year, check next year
            if ($diff < 0) {
                $to->addYear();
                $diff = $from->diffInDays($to, false);
            }

            return $diff;
        } catch (\Exception $e) {
            return PHP_INT_MAX;
        }
    }

    /**
     * Check if a date (MM-DD format) falls within a date range
     * Handles ranges that span year boundary (e.g., 12-15 to 01-31)
     */
    private function isDateInRange(string $date, string $startDate, string $endDate): bool
    {
        // Handle leap year dates (02-29)
        if ($date === '02-29' && ! now()->isLeapYear()) {
            return false;
        }

        if ($startDate <= $endDate) {
            // Range within same year (e.g., 03-01 to 10-31)
            return $date >= $startDate && $date <= $endDate;
        } else {
            // Range spans year boundary (e.g., 12-01 to 02-28)
            return $date >= $startDate || $date <= $endDate;
        }
    }

    /**
     * Sort seasons by start date
     */
    private function sortSeasonsByDate(array $seasons): array
    {
        usort($seasons, function ($a, $b) {
            // Year-round seasons come first
            if ($a['is_year_round'] ?? false) {
                return -1;
            }
            if ($b['is_year_round'] ?? false) {
                return 1;
            }

            // Sort by start date
            $startA = $a['start_date'] ?? '01-01';
            $startB = $b['start_date'] ?? '01-01';

            return strcmp($startA, $startB);
        });

        return $seasons;
    }

    /**
     * Format date range for display
     */
    private function formatDateRange(?string $startDate, ?string $endDate, string $language = 'de'): string
    {
        if (empty($startDate) || empty($endDate)) {
            return '';
        }

        try {
            // Parse dates (using current year as dummy year)
            $start = Carbon::createFromFormat('m-d', $startDate)->year(now()->year);
            $end = Carbon::createFromFormat('m-d', $endDate)->year(now()->year);

            if ($language === 'en') {
                return $start->format('M j').' - '.$end->format('M j');
            } else {
                return $start->format('d.m.').' - '.$end->format('d.m.');
            }
        } catch (\Exception $e) {
            return $startDate.' - '.$endDate;
        }
    }
}
