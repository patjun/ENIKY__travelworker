<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model {
	use SoftDeletes;

	protected $fillable = [
		'name', 'street', 'zip', 'city', 'country', 'latitude', 'longitude',
		'cid', 'place_id', 'task_id', 'task_post_output', 'task_get_output',
		'location_code', 'language_code', 'business_data', 'last_dataforseo_update',
		'job_status', 'post_attempts', 'get_attempts', 'phone', 'website',
		'description', 'category', 'rating_value', 'rating_votes_count',
		'opening_hours', 'attributes', 'main_image_url', 'is_claimed',
		'price_level', 'additional_categories',
		'en_name', 'en_street', 'en_city', 'en_country', 'en_phone', 'en_website',
		'en_description', 'en_category', 'en_opening_hours', 'en_attributes',
		'en_main_image_url', 'en_price_level', 'en_additional_categories'
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
	];
}
