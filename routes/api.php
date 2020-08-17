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

use App\Http\Controllers\Home;
use App\Http\Controllers\Reviews;
use Luracast\Restler\Defaults;
use Luracast\Restler\OpenApi3\Explorer;
use Luracast\Restler\Router;

try {
    Defaults::$productionMode = getenv('APP_ENV') == 'production';
    Router::mapApiClasses([
        '' => Explorer::class,
        //Home::class,
        Reviews::class,
    ]);
    $routes = Router::toArray();
} catch (Throwable $throwable) {
    die($throwable->getMessage() . PHP_EOL);
}
