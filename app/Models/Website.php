<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Get the pages for the website.
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }
}
