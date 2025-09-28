<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model {
	use SoftDeletes;

	protected static function booted()
	{
		static::saving(function ($location) {
			$location->generateWidgets();
		});
	}

	protected $fillable = [
		'name', 'street', 'zip', 'city', 'country', 'latitude', 'longitude',
		'cid', 'place_id', 'task_id', 'task_post_output', 'task_get_output',
		'location_code', 'language_code', 'business_data', 'last_dataforseo_update',
		'job_status', 'post_attempts', 'get_attempts', 'phone', 'website',
		'description', 'category', 'rating_value', 'rating_votes_count',
		'opening_hours', 'attributes', 'main_image_url', 'is_claimed',
		'price_level', 'additional_categories', 'opening_hours_html', 'structured_data',
		'contact_info_html', 'rating_html', 'accessibility_html',
		'en_name', 'en_street', 'en_city', 'en_country', 'en_phone', 'en_website',
		'en_description', 'en_category', 'en_opening_hours', 'en_attributes',
		'en_main_image_url', 'en_price_level', 'en_additional_categories',
		'en_opening_hours_html', 'en_structured_data', 'en_contact_info_html', 'en_rating_html', 'en_accessibility_html',
		'en_task_id', 'en_task_post_output', 'en_task_get_output', 'en_business_data',
		'en_last_dataforseo_update', 'en_job_status', 'en_post_attempts', 'en_get_attempts'
	];

	protected $casts = [
		'task_post_output' => 'array',
		'task_get_output' => 'array',
		'business_data' => 'array',
		'last_dataforseo_update' => 'datetime',
		'latitude' => 'decimal:7',
		'longitude' => 'decimal:7',
		'opening_hours' => 'array',
		'attributes' => 'array',
		'additional_categories' => 'array',
		'is_claimed' => 'boolean',
		'en_opening_hours' => 'array',
		'en_attributes' => 'array',
		'en_additional_categories' => 'array',
		'en_task_post_output' => 'array',
		'en_task_get_output' => 'array',
		'en_business_data' => 'array',
		'en_last_dataforseo_update' => 'datetime',
	];

	public function generateWidgets()
	{
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
		$city = $language === 'en' ? ($this->en_city ?: $this->city) : $this->city;
		$country = $language === 'en' ? ($this->en_country ?: $this->country) : $this->country;
		$phone = $language === 'en' ? ($this->en_phone ?: $this->phone) : $this->phone;
		$website = $language === 'en' ? ($this->en_website ?: $this->website) : $this->website;

		$html = "<div class=\"contact-info-widget\">\n";

		// Header with business name
		if ($name) {
			$html .= "  <div class=\"contact-header\">\n";
			$html .= "    <h2 class=\"contact-name\">{$name}</h2>\n";
			$html .= "  </div>\n";
		}

		$html .= "  <div class=\"contact-details\">\n";

		// Address section
		if ($street || $city || $country || $this->zip) {
			$html .= "    <div class=\"contact-item contact-address\">\n";
			$html .= "      <div class=\"contact-icon\">📍</div>\n";
			$html .= "      <div class=\"contact-info\">\n";

			$addressParts = [];
			if ($street) $addressParts[] = $street;
			if ($this->zip && $city) {
				$addressParts[] = $this->zip . ' ' . $city;
			} elseif ($city) {
				$addressParts[] = $city;
			}
			if ($country) $addressParts[] = $country;

			foreach ($addressParts as $part) {
				$html .= "        <div class=\"address-line\">{$part}</div>\n";
			}

			$html .= "      </div>\n";
			$html .= "    </div>\n";
		}

		// Phone section
		if ($phone) {
			$html .= "    <div class=\"contact-item contact-phone\">\n";
			$html .= "      <div class=\"contact-icon\">📞</div>\n";
			$html .= "      <div class=\"contact-info\">\n";
			$html .= "        <a href=\"tel:{$phone}\" class=\"contact-link\">{$phone}</a>\n";
			$html .= "      </div>\n";
			$html .= "    </div>\n";
		}

		// Website section
		if ($website) {
			$websiteText = $language === 'en' ? 'Visit Website' : 'Website besuchen';
			$html .= "    <div class=\"contact-item contact-website\">\n";
			$html .= "      <div class=\"contact-icon\">🌐</div>\n";
			$html .= "      <div class=\"contact-info\">\n";
			$html .= "        <a href=\"{$website}\" target=\"_blank\" rel=\"noopener\" class=\"contact-link\">{$websiteText}</a>\n";
			$html .= "      </div>\n";
			$html .= "    </div>\n";
		}

		$html .= "  </div>\n";
		$html .= "</div>";

		return $html;
	}

	public function generateAccessibilityHtml($language = 'de')
	{
		// Get the correct attributes field based on language
		$attributesData = $language === 'en' ? $this->en_attributes : $this->attributes;

		// Return null if no attributes data
		if (!$attributesData) {
			return null;
		}

		// Extract accessibility features
		$availableFeatures = $attributesData['available_attributes']['accessibility'] ?? [];
		$unavailableFeatures = $attributesData['unavailable_attributes']['accessibility'] ?? [];

		// Return null if no accessibility features at all
		if (empty($availableFeatures) && empty($unavailableFeatures)) {
			return null;
		}

		// Set up translations
		$titleText = $language === 'en' ? 'Accessibility' : 'Barrierefreiheit';

		// Define feature translations
		$featureTranslations = [
			'has_wheelchair_accessible_entrance' => [
				'de' => 'Rollstuhlgerechter Eingang',
				'en' => 'Wheelchair accessible entrance'
			],
			'has_wheelchair_accessible_seating' => [
				'de' => 'Rollstuhlgerechte Sitzplätze',
				'en' => 'Wheelchair accessible seating'
			],
			'has_wheelchair_accessible_parking' => [
				'de' => 'Rollstuhlgerechte Parkplätze',
				'en' => 'Wheelchair accessible parking'
			],
			'has_wheelchair_accessible_restroom' => [
				'de' => 'Rollstuhlgerechte Toiletten',
				'en' => 'Wheelchair accessible restroom'
			],
			'has_hearing_loop' => [
				'de' => 'Induktionsschleife',
				'en' => 'Hearing loop'
			]
		];

		// Start building HTML
		$output = '<div class="accessibility-widget">' . "\n";
		$output .= '  <div class="accessibility-header">' . "\n";
		$output .= '    <h3 class="accessibility-title">♿ ' . $titleText . '</h3>' . "\n";
		$output .= '  </div>' . "\n";
		$output .= '  <div class="accessibility-features">' . "\n";

		// Add available features
		foreach ($availableFeatures as $featureKey) {
			if (isset($featureTranslations[$featureKey])) {
				$feature = $featureTranslations[$featureKey];
				$label = $feature[$language];

				$output .= '    <div class="accessibility-item accessibility-available">' . "\n";
				$output .= '      <span class="accessibility-status accessibility-yes">✓</span>' . "\n";
				$output .= '      <span class="accessibility-label">' . $label . '</span>' . "\n";
				$output .= '    </div>' . "\n";
			}
		}

		// Add unavailable features
		foreach ($unavailableFeatures as $featureKey) {
			if (isset($featureTranslations[$featureKey])) {
				$feature = $featureTranslations[$featureKey];
				$label = $feature[$language];

				$output .= '    <div class="accessibility-item accessibility-unavailable">' . "\n";
				$output .= '      <span class="accessibility-status accessibility-no">✗</span>' . "\n";
				$output .= '      <span class="accessibility-label">' . $label . '</span>' . "\n";
				$output .= '    </div>' . "\n";
			}
		}

		$output .= '  </div>' . "\n";
		$output .= '</div>';

		return $output;
	}

	public function generateRatingHtml($language = 'de')
	{
		if (!$this->rating_value || !$this->rating_votes_count) {
			return null;
		}

		$ratingText = $language === 'en' ? 'Rating' : 'Bewertung';
		$reviewsText = $language === 'en' ? 'Reviews' : 'Bewertungen';
		$outOfText = $language === 'en' ? 'out of' : 'von';

		$html = "<div class=\"rating-widget\">\n";

		// Header
		$html .= "  <div class=\"rating-header\">\n";
		$html .= "    <h3 class=\"rating-title\">{$ratingText}</h3>\n";
		$html .= "  </div>\n";

		$html .= "  <div class=\"rating-content\">\n";

		// Main rating display
		$html .= "    <div class=\"rating-main\">\n";
		$html .= "      <div class=\"rating-score\">{$this->rating_value}</div>\n";
		$html .= "      <div class=\"rating-details\">\n";

		// Star display
		$html .= "        <div class=\"rating-stars\">\n";
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
		$html .= "        <div class=\"rating-text\">\n";
		$html .= "          <span class=\"rating-out-of\">{$outOfText} 5</span>\n";
		$html .= "        </div>\n";

		$html .= "      </div>\n";
		$html .= "    </div>\n";

		// Review count
		$html .= "    <div class=\"rating-reviews\">\n";
		$html .= "      <span class=\"reviews-count\">" . number_format($this->rating_votes_count) . "</span>\n";
		$html .= "      <span class=\"reviews-label\">{$reviewsText}</span>\n";
		$html .= "    </div>\n";

		$html .= "  </div>\n";
		$html .= "</div>";

		return $html;
	}

	public function generateOpeningHoursHtml($language = 'de')
	{
		$openingHours = $language === 'en' ? $this->en_opening_hours : $this->opening_hours;

		if (!$openingHours || !isset($openingHours['work_hours']['timetable'])) {
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
			'sunday' => 'Sunday'
		] : [
			'monday' => 'Montag',
			'tuesday' => 'Dienstag',
			'wednesday' => 'Mittwoch',
			'thursday' => 'Donnerstag',
			'friday' => 'Freitag',
			'saturday' => 'Samstag',
			'sunday' => 'Sonntag'
		];

		$closedText = $language === 'en' ? 'Closed' : 'Geschlossen';
		$openingHoursText = $language === 'en' ? 'Opening Hours' : 'Öffnungszeiten';

		$html = "<div class=\"opening-hours-widget\">\n";
		$html .= "  <div class=\"opening-hours-header\">\n";
		$html .= "    <h2 class=\"opening-hours-title\">{$openingHoursText}</h2>\n";
		$html .= "  </div>\n";

		$dayOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

		foreach ($dayOrder as $day) {
			$dayLabel = $dayNames[$day];
			$html .= "  <div class=\"opening-hours-day\">\n";
			$html .= "    <span class=\"opening-hours-day-name\">{$dayLabel}</span>\n";

			if (isset($timetable[$day]) && !empty($timetable[$day])) {
				$hours = $timetable[$day][0];

				if ($language === 'en') {
					// 12-hour format for English
					$openTime = $this->formatTime12Hour($hours['open']['hour'], $hours['open']['minute']);
					$closeTime = $this->formatTime12Hour($hours['close']['hour'], $hours['close']['minute']);
				} else {
					// 24-hour format for German
					$openTime = sprintf('%02d:%02d', $hours['open']['hour'], $hours['open']['minute']);
					$closeTime = sprintf('%02d:%02d', $hours['close']['hour'], $hours['close']['minute']);
				}

				$html .= "    <span class=\"opening-hours-time\">{$openTime} - {$closeTime}</span>\n";
			} else {
				$html .= "    <span class=\"opening-hours-closed\">{$closedText}</span>\n";
			}

			$html .= "  </div>\n";
		}

		$html .= "</div>";

		return $html;
	}

	public function generateStructuredData($language = 'de')
	{
		$name = $language === 'en' ? ($this->en_name ?: $this->name) : $this->name;
		$street = $language === 'en' ? ($this->en_street ?: $this->street) : $this->street;
		$city = $language === 'en' ? ($this->en_city ?: $this->city) : $this->city;
		$country = $language === 'en' ? ($this->en_country ?: $this->country) : $this->country;
		$phone = $language === 'en' ? ($this->en_phone ?: $this->phone) : $this->phone;
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

		if ($street || $city || $country || $this->zip) {
			$structuredData['address'] = [
				'@type' => 'PostalAddress'
			];
			if ($street) $structuredData['address']['streetAddress'] = $street;
			if ($city) $structuredData['address']['addressLocality'] = $city;
			if ($country) $structuredData['address']['addressCountry'] = $country;
			if ($this->zip) $structuredData['address']['postalCode'] = $this->zip;
		}

		if ($this->latitude && $this->longitude) {
			$structuredData['geo'] = [
				'@type' => 'GeoCoordinates',
				'latitude' => (float) $this->latitude,
				'longitude' => (float) $this->longitude
			];
		}

		if ($phone) {
			$structuredData['telephone'] = $phone;
		}

		if ($website) {
			$structuredData['url'] = $website;
		}

		if ($description) {
			$structuredData['description'] = $description;
		}

		if ($imageUrl) {
			$structuredData['image'] = [$imageUrl];
		}

		if ($this->rating_value && $this->rating_votes_count) {
			$structuredData['aggregateRating'] = [
				'@type' => 'AggregateRating',
				'ratingValue' => (float) $this->rating_value,
				'reviewCount' => (int) $this->rating_votes_count,
				'bestRating' => 5
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
			'sunday' => 'Sunday'
		];

		$specifications = [];
		$groupedDays = [];

		foreach ($timetable as $day => $hours) {
			if (!empty($hours)) {
				$timeKey = sprintf('%02d:%02d-%02d:%02d',
					$hours[0]['open']['hour'],
					$hours[0]['open']['minute'],
					$hours[0]['close']['hour'],
					$hours[0]['close']['minute']
				);
				$groupedDays[$timeKey][] = $dayMapping[$day];
			}
		}

		foreach ($groupedDays as $timeRange => $days) {
			list($openTime, $closeTime) = explode('-', $timeRange);

			$specifications[] = [
				'@type' => 'OpeningHoursSpecification',
				'dayOfWeek' => count($days) === 1 ? $days[0] : $days,
				'opens' => $openTime,
				'closes' => $closeTime
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
}
