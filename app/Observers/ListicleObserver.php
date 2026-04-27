<?php

namespace App\Observers;

use App\Models\Listicle;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ListicleObserver
{
    /**
     * Handle the Listicle "saved" event.
     */
    public function saved(Listicle $listicle): void
    {
        if ($listicle->image && $listicle->wasChanged('image')) {
            $this->generateWebPVersion($listicle->image);
        }
    }

    /**
     * Generate WebP version of the uploaded image.
     */
    protected function generateWebPVersion(string $imagePath): void
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($imagePath)) {
            return;
        }

        $fullPath = $disk->path($imagePath);
        $webpPath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $fullPath);

        try {
            Image::read($fullPath)
                ->toWebp(85) // Qualität 85%
                ->save($webpPath);
        } catch (\Exception $e) {
            // Log the error but don't fail the entire operation
            \Log::error('Failed to generate WebP version for image: '.$imagePath, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Listicle "deleted" event.
     */
    public function deleted(Listicle $listicle): void
    {
        //
    }

    /**
     * Handle the Listicle "restored" event.
     */
    public function restored(Listicle $listicle): void
    {
        //
    }

    /**
     * Handle the Listicle "force deleted" event.
     */
    public function forceDeleted(Listicle $listicle): void
    {
        //
    }
}
