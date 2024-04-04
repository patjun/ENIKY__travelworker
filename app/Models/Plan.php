<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Taxonomy::class);
    }
}
