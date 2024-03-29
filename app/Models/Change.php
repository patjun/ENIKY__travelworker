<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
