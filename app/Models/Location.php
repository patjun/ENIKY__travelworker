<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model {
	use SoftDeletes;

	protected $fillable = [
		'name', 'street', 'zip', 'city', 'country', 'latitude', 'longitude',
		'cid', 'place_id', 'task_id', 'task_post_output', 'task_get_output',
		'location_code', 'language_code', 'business_data', 'last_dataforseo_update'
	];

	protected $casts = [
		'business_data' => 'array',
		'task_post_output' => 'array',
		'task_get_output' => 'array',
		'last_dataforseo_update' => 'datetime',
	];
}
