<?php


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the Restler 4. Enjoy building your API!
|
*/

use App\Http\Controllers\Reviews;
use Luracast\Restler\Data\ErrorResponse;
use Luracast\Restler\Defaults;
use Luracast\Restler\GraphQL\GraphQL;
use Luracast\Restler\OpenApi3\Explorer;
use Luracast\Restler\Router;

try {
    Defaults::$productionMode = getenv('APP_ENV') == 'production';
    Router::mapApiClasses([
        '' => Explorer::class,
        //Home::class,
        Reviews::class,
        GraphQL::class,
    ]);
    GraphQL::mapApiClasses([
        Reviews::class
    ]);
    $routes = Router::toArray();
} catch (Throwable $throwable) {
    die(json_encode(new ErrorResponse($throwable,true), JSON_PRETTY_PRINT) . PHP_EOL);
}
