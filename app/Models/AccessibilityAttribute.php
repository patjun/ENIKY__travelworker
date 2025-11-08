<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccessibilityAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'placeholder',
        'name_en',
        'name_de',
        'description_en',
    ];

    public function attractions(): BelongsToMany
    {
        return $this->belongsToMany(Attraction::class, 'attraction_accessibility_attribute');
    }
}
