<?php

use App\Http\Controllers\DFSKeywordForKeywordController;
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

/*
Route::get( '/', function () {
    return view( 'welcome' );
} );
*/

Route::get('/keyword_tasks_ready', [DFSKeywordForKeywordController::class, 'tasksReady']);
Route::get('/keyword/{keyword}', [DFSKeywordForKeywordController::class, 'postTask']);

Route::get('/posts', function () {

    $page = 0;
    $domain = 'https://fromplacetoplace.travel';
    $website_id = 2;

    do {
        $page++;

        $response = Http::get($domain.'/wp-json/wp/v2/posts', [
            'page' => $page,
            'per_page' => 50,
            'lang' => 'de',
        ]);
        $totalPages = $response->header('x-wp-totalpages');

        foreach ($response->json() as $post) {

            \App\Models\Post::updateOrCreate(
                [
                    'post_id' => $post['id'],
                    'website_id' => $website_id,
                ],
                [
                    'post_author' => $post['author'],
                    'post_date' => $post['date'],
                    'post_date_gmt' => $post['date_gmt'],
                    'post_content' => $post['content']['rendered'],
                    'post_title' => $post['title']['rendered'],
                    'post_excerpt' => $post['excerpt']['rendered'],
                    'post_status' => $post['status'],
                    'comment_status' => $post['comment_status'],
                    'ping_status' => $post['ping_status'],
                    // 'post_password'         => $post['password'],
                    'post_name' => $post['slug'],
                    // 'to_ping'               => $post['to_ping'],
                    // 'pinged'                => $post['pinged'],
                    'post_modified' => $post['modified'],
                    'post_modified_gmt' => $post['modified_gmt'],
                    'post_content_filtered' => $post['content']['protected'],
                    // 'post_parent'           => $post['parent'],
                    'guid' => $post['guid']['rendered'],
                    // 'menu_order'            => $post['menu_order'],
                    'post_type' => $post['type'],
                    // 'post_mime_type'        => $post['mime_type'],
                    // 'comment_count'         => $post['comment_count']
                ]
            );
        }

    } while ($page < $totalPages || $page > 10);

});

Route::get('get_jwt_token', function () {

    $username = 'patjun';
    $password = 'Q@$lHH0lr)P9@Ka$Vj';
    $site_url = 'https://www.retail-store-metrics.com';

    $url = $site_url.'/wp-json/jwt-auth/v1/token';

    $response = Http::post(
        $url, [
            'username' => $username,
            'password' => $password,
        ]
    );

    print_r($response->json());

});

Route::get('/allposts', function () {

    $username = 'patjun';
    $application_password = 'Ho9c 9vGs AOBG nXb0 FPpr W5vO';
    $jwt_token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL3d3dy5yZXRhaWwtc3RvcmUtbWV0cmljcy5jb20iLCJpYXQiOjE3MDgyMzc1OTYsIm5iZiI6MTcwODIzNzU5NiwiZXhwIjoxNzA4ODQyMzk2LCJkYXRhIjp7InVzZXIiOnsiaWQiOiIxIn19fQ.-IaG83dvuo4kz925MmdwxC0_ocfCwIKp_sbFNX6c52k';
    $site_url = 'https://www.retail-store-metrics.com';

    $params = [
        'per_page' => 100,
        'status' => 'future,publish',
    ];
    $url = $site_url.'/wp-json/wp/v2/posts';

    // ray()->clearAll()->showApp();

    // $response = Http::withBasicAuth( $username, $application_password )->get( $url );
    // ray( $response->json() );

    $response = Http::withToken($jwt_token)->get($url, $params);
    ray($response->json());

    $posts = $response->json();

});

Route::get('/categories', function () {

    $page = 0;
    $domain = 'https://fromplacetoplace.travel';
    $website_id = 2;

    do {
        $page++;

        $response = Http::get($domain.'/wp-json/wp/v2/categories', [
            'page' => $page,
            'per_page' => 50,
        ]);
        $totalPages = $response->header('x-wp-totalpages');

        foreach ($response->json() as $term) {

            \App\Models\Taxonomy::updateOrCreate(
                [
                    'term_id' => $term['id'],
                    'website_id' => $website_id,
                ],
                [
                    'term_name' => $term['name'],
                    'term_taxonomy' => $term['taxonomy'],
                    'term_parent_id' => $term['parent'],
                    'term_count' => $term['count'],
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

});
