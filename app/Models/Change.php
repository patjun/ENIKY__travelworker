<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Change extends Model
{
    use HasFactory;

    protected $fillable = ['page_id', 'modification_date', 'modification_description'];

    protected $casts = [
        'modification_date' => 'date:Y-m-d',
    ];

    /**
     * Get the page for this change.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
