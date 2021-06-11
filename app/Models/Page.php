<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    /**
     * Get the changes for the page.
     */
    public function changes()
    {
        return $this->hasMany(Change::class);
    }
}
