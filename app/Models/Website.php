<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    use HasFactory;

    /**
     * Get the pages for the website.
     */
    public function pages()
    {
        return $this->hasMany(Page::class);
    }
}
