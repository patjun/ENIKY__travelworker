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
		'en_name', 'en_street', 'en_city', 'en_country', 'en_phone', 'en_website',
		'en_description', 'en_category', 'en_opening_hours', 'en_attributes',
		'en_main_image_url', 'en_price_level', 'en_additional_categories',
		'en_opening_hours_html', 'en_structured_data',
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
		$this->opening_hours_html = $this->generateOpeningHoursHtml('de');
		$this->structured_data = $this->generateStructuredData('de');
		$this->en_opening_hours_html = $this->generateOpeningHoursHtml('en');
		$this->en_structured_data = $this->generateStructuredData('en');
	}

	public function generateOpeningHoursHtml($language = 'de')
	{
		$openingHours = $language === 'en' ? $this->en_opening_hours : $this->opening_hours;

		if (!$openingHours || !isset($openingHours['work_hours']['timetable'])) {
			return null;
		}

		$timetable = $openingHours['work_hours']['timetable'];
		$dayNames = $language === 'en' ? [
			'monday' => 'MONDAY',
			'tuesday' => 'TUESDAY',
			'wednesday' => 'WEDNESDAY',
			'thursday' => 'THURSDAY',
			'friday' => 'FRIDAY',
			'saturday' => 'SATURDAY',
			'sunday' => 'SUNDAY'
		] : [
			'monday' => 'MONTAG',
			'tuesday' => 'DIENSTAG',
			'wednesday' => 'MITTWOCH',
			'thursday' => 'DONNERSTAG',
			'friday' => 'FREITAG',
			'saturday' => 'SAMSTAG',
			'sunday' => 'SONNTAG'
		];

		$closedText = $language === 'en' ? 'CLOSED' : 'GESCHLOSSEN';
		$openingHoursText = $language === 'en' ? 'OPENING HOURS' : 'ÖFFNUNGSZEITEN';

		$html = "<div style=\"background-color: #333; color: white; padding: 20px; font-family: Arial, sans-serif; width: 400px;\">\n";
		$html .= "  <div style=\"text-align: center; border-top: 3px solid white; border-bottom: 3px solid white; padding: 10px 0; margin-bottom: 20px;\">\n";
		$html .= "    <h2 style=\"margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 2px;\">{$openingHoursText}</h2>\n";
		$html .= "  </div>\n";

		$dayOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

		foreach ($dayOrder as $day) {
			$dayLabel = $dayNames[$day];
			$html .= "  <div style=\"display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 18px;\">\n";
			$html .= "    <span style=\"font-weight: bold;\">{$dayLabel}</span>\n";

			if (isset($timetable[$day]) && !empty($timetable[$day])) {
				$hours = $timetable[$day][0];
				$openTime = sprintf('%02d:%02d', $hours['open']['hour'], $hours['open']['minute']);
				$closeTime = sprintf('%02d:%02d', $hours['close']['hour'], $hours['close']['minute']);
				$html .= "    <span>{$openTime} - {$closeTime}</span>\n";
			} else {
				$html .= "    <span>{$closedText}</span>\n";
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
}
