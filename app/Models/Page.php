<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = ['website_id', 'url'];

    /**
     * Get the website for this page.
     */
    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id');
    }

    /**
     * Get the changes for the page.
     */
    public function changes()
    {
        return $this->hasMany(Change::class);
    }
}
