<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Change extends Model
{
    use HasFactory;

    /**
     * Get the page for this change.
     */
    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
