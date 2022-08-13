<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class LoadTerms extends Action {
    use InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * @param \Laravel\Nova\Fields\ActionFields $fields
     * @param \Illuminate\Support\Collection $models
     *
     * @return mixed
     */
    public function handle( ActionFields $fields, Collection $models ) {
        ray( $models );
        foreach ( $models as $model ) {
            $page       = 0;
            $domain     = $model->url;
            $website_id = $model->id;

            do {
                $page ++;

                $response   = Http::get( $domain . '/wp-json/wp/v2/categories', [
                    'page'     => $page,
                    'per_page' => 50,
                ] );
                $totalPages = $response->header( 'x-wp-totalpages' );

                foreach ( $response->json() as $term ) {

                    \App\Models\Taxonomy::updateOrCreate(
                        [
                            'term_id'    => $term['id'],
                            'website_id' => $website_id
                        ],
                        [
                            'term_name'      => $term['name'],
                            'term_taxonomy'  => $term['taxonomy'],
                            'term_parent_id' => $term['parent'],
                            'term_count'     => $term['count']
                        ]
                    );
                }

            } while ( $page < $totalPages || $page > 10 );
        }
    }

    /**
     * Get the fields available on the action.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     *
     * @return array
     */
    public function fields( NovaRequest $request ) {
        return [];
    }
}
