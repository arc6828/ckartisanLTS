<?php

use App\Http\Controllers\API\BookController;
use App\Http\Controllers\API\FileController;
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\MediumController;
use App\Http\Controllers\API\PublicationController;
use App\Http\Controllers\API\TambonController;
use App\Http\Controllers\API\WongnaiController;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::apiResource('/publication', PublicationController::class);
Route::prefix('medium')->group(function () {
    Route::get('test', function () {
        // return "start";
        $feed_url = "https://medium.com/feed/ckartisan";
        try {
            //code...
            $xml = simplexml_load_file($feed_url, "SimpleXMLElement", LIBXML_NOCDATA);
            // $xml = simplexml_load_file($feed_url, "SimpleXMLElement", LIBXML_NOBLANKS);
            // $xml = simplexml_load_file($feed_url);

            if ($xml === false) {
                return "Error fetching RSS feed.";
            } else {
                foreach ($xml->channel->item as $item) {
                    // echo "<h2><a href='{$item->link}'>{$item->title}</a></h2>";
                    // echo "<p>{$item->description}</p>";
                }
                // return "data";
                return response()->json($xml);
            }
        } catch (\Throwable $th) {
            //throw $th;
            return "thrown";
        }
    });
    // http://localhost:8000/api/medium/articles/feed/ckartisan
    Route::prefix('articles')->group(function () {
        Route::get('/feed/{publication?}', [MediumController::class, 'index']);
        Route::get('/feed/{publication?}/tagged/{tagname?}', [MediumController::class, 'index']);
    });
    // http://localhost:8000/api/medium/articles/update/feed/ckartisan
    Route::prefix('articles/update')->group(function () {
        Route::get('/feed/{publication?}', [MediumController::class, 'update']);
        Route::get('/feed/{publication?}/tagged/{tagname?}', [MediumController::class, 'update']);
    });


    Route::get('/feed/{publication?}', [MediumController::class, 'feed']);
    Route::get('/feed/{publication?}/tagged/{tagname?}', [MediumController::class, 'feed']);
    Route::get('/feednocache/{publication?}', [MediumController::class, 'feedNoCache']);
    Route::get('/feednocache/{publication?}/tagged/{tagname?}', [MediumController::class, 'feedNoCache']);
    // tagged/[tag-name]
    // Route::get('/ckartisan', [MediumController::class, 'ckartisan']);
    // Route::get('/ckartisan2', [MediumController::class, 'ckartisan2']);
});

// PROVINCE - AMPHOE - TAMBON
Route::get('/provinces', [TambonController::class, 'getProvinces']);
Route::get('/amphoes', [TambonController::class, 'getAmphoes']);
Route::get('/tambons', [TambonController::class, 'getTambons']);
Route::get('/zipcodes', [TambonController::class, 'getZipcodes']);

// https://3166-202-29-39-124.ngrok-free.app
Route::apiResource('/mywongnai/webhook', WongnaiController::class);
// Route::post('/mywongnai/webhook', [WongnaiController::class, 'store'] );

Route::get('/article', function () {
    $articles = Article::whereNotNull('credit')
        ->orderBy('pubDate', 'desc')
        ->get();
    return json_encode($articles, JSON_UNESCAPED_UNICODE);
});

Route::get('/article/tagged/{tagname}', function ($tagname) {
    $articles = Article::whereNotNull('credit')
        ->where('category', 'like', "%$tagname%")
        ->orderBy('pubDate', 'desc')
        ->get();
    return json_encode($articles, JSON_UNESCAPED_UNICODE);
});


Route::get('/article/slug', function () {
    $articles = Article::whereNull('slug')->get();

    foreach ($articles as $item) {
        $requestData = ["slug" => $item->id];
        $item->update($requestData);
        // echo "{$item->id} {$item->slug} <br>";
        // $item->update(["slug"=>$item->id]);
    }

    return "Successfully updated";
});

Route::get('/article/{id}', function ($id) {

    // $article = Article::findOrFail($id);
    $article = Article::where("id", $id)->orWhere("slug", $id)->firstOrFail();

    $related = Article::whereNotNull('credit')
        ->where('credit', $article->credit)
        ->where('id', '<>', $id)
        ->orderBy('pubDate', 'desc')
        ->limit(5)
        ->get();
    $tags = json_decode($article->category);


    // english tags
    $english_tags = array_filter($tags, function ($item) {
        return !preg_match('/[^A-Za-z0-9-]/', $item);
    });
    $english_tags = array_values($english_tags);

    // print_r($tags);
    // print_r($english_tags);

    // return ;

    // $english_tag = count($english_tags) > 0 ? $english_tags[0] : $tags[0];

    $tagged = Article::whereNotNull('credit')
        ->where(function (Builder $query) use ($english_tags) {
            foreach ($english_tags as $tag) {
                $query->orWhere('category', 'like', "%$tag%");
            }
        })
        // ->where('category', 'like', "%$english_tag%")        
        ->where('id', '<>', $id)
        ->orderBy('pubDate', 'desc')
        ->limit(5)
        ->get();
    $writer = $article->writer;
    $article_set = [
        "article" => $article,
        "related" => $related,
        "tagged" => $tagged,
        "writer" => $writer,
    ];
    return json_encode($article_set, JSON_UNESCAPED_UNICODE);
});

Route::apiResource('location', LocationController::class);

Route::post('file', [FileController::class, 'upload']);

Route::apiResource('book', BookController::class);

Route::post('/sanctum/token', function (Request $request) {
    $user = User::where('email', $request->email)->first();
    if (!$user || !Hash::check($request->password, $user->password)) {
        return ['email' => ['The provided credentials are incorrect.']];
    }
    return ['token' => $user->createToken($request->device_name)->plainTextToken];
});

Route::post('/sanctum/token/register', function (Request $request) {
    $user = User::where('email', $request->email)->first();
    if ($user) {
        return ['email' => ['The email is already in use.']];
    }
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);
    return ['token' => $user->createToken($request->device_name)->plainTextToken];
});
