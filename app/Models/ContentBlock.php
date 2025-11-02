<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_page_id',
        'blockable_type',
        'blockable_id',
        'order',
        'language',
    ];

    public function contentPage(): BelongsTo
    {
        return $this->belongsTo(ContentPage::class);
    }

    public function blockable(): MorphTo
    {
        return $this->morphTo();
    }
}
