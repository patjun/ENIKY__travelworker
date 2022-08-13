<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use RickWest\WordPress\Facades\WordPress;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get( '/', function () {
    return view( 'welcome' );
} );

Route::get( '/categories', function () {

    $page = 0;
    $domain = 'https://fromplacetoplace.travel';
    $website_id = 2;

    do {
        $page++;

        $response = Http::get( $domain.'/wp-json/wp/v2/categories', [
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

    } while ($page < $totalPages || $page > 10);



    /*

    - Import Categories for different sites
    - Save categories in a model
    - Add modell for idea just markdown, the modell should have a reference to the category

     */

    /*
    $client = new \RickWest\WordPress\Client('https://fromplacetoplace.travel');
    WordPress::
    $categories = WordPress::categories()->perPage(10)->page(1)->get();
    dd($categories);
    */

} );
