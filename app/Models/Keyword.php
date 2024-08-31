<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    use HasFactory;

    protected $fillable = [
        'keyword',
        'date',
        'post_id',
        'parent_id',
        'task_post_output',
        'task_id',
        'task_get_output',
        'location_code',
        'language_code',
        'search_partners',
        'competition',
        'competition_index',
        'search_volume',
        'low_top_of_page_bid',
        'high_top_of_page_bid',
        'cpc',
        'monthly_searches',
        'keyword_annotations',
        'is_processed',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function parents()
    {
        return $this->belongsToMany(Keyword::class, 'keyword_parent', 'keyword_id', 'parent_id');
    }

    public function children()
    {
        return $this->belongsToMany(Keyword::class, 'keyword_parent', 'parent_id', 'keyword_id');
    }

}
