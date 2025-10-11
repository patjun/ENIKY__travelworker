<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Services\DataForSeoService;
use Illuminate\Http\Request;

class DFSKeywordForKeywordController extends Controller {
    private $dataForSeoService;

    public function __construct( DataForSeoService $dataForSeoService ) {
        $this->dataForSeoService = $dataForSeoService;
    }

    public function postTask( Request $request, $routeKeyword ) {

        // if keyword is already in database, return
        $keyword = Keyword::where( 'keyword', $routeKeyword )->first();
        if ( ! empty($keyword->task_get_output) ) {
            return response()->json( $keyword );
        }

        $routeKeyword = $request->input( 'keywords', $routeKeyword );
        $locationCode = $request->input( 'location_code', 2276 );
        $languageCode = $request->input( 'language_code', 'de' );
        $sortBy       = $request->input( 'sort_by', 'relevance' );

        $result = $this->dataForSeoService->getKeywordsForKeywords( [
            [
                'location_code' => $locationCode,
                'keywords'      => [ $routeKeyword ],
                'language_code' => $languageCode,
                'sort_by'       => $sortBy,
            ]
        ] );

        // write to database
        $keyword = Keyword::updateOrCreate(
            [ 'keyword' => $routeKeyword ], // The attributes to search by
            [
                'keyword'          => $routeKeyword,
                'date'             => now(),
                'task_post_output' => json_encode( $result ),
                'task_id'          => $result['tasks'][0]['id'],
            ]
        );

        return response()->json( $result );
    }

    public function tasksReady() {

        $result = $this->dataForSeoService->KeywordsForKeywordsTasksReady();

        if ( ! is_null( $result['tasks'][0]['result'] ) ) {
            foreach ( $result['tasks'][0]['result'] as $task ) {

                $task_result = $this->dataForSeoService->KeywordsForKeywordsTaskGet( $task['id'] );

                $keyword = Keyword::updateOrCreate(
                    [ 'task_id' => $task_result['tasks'][0]['id'] ],
                    [
                        'keyword'         => $task_result['tasks'][0]['data']['keywords'][0],
                        'date'            => now(),
                        'task_id'         => $task_result['tasks'][0]['id'],
                        'task_get_output' => json_encode( $task_result['tasks'][0]['result'] ),
                    ]
                );
            }
        }

        return response()->json( $result );
    }
}
