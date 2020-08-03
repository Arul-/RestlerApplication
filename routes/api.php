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
use Luracast\Restler\OpenApi3\Explorer;
use Luracast\Restler\Router;

try {
    Router::mapApiClasses([
        '' => Home::class,
        Explorer::class
    ]);
} catch (Throwable $throwable) {
    echo $throwable->getMessage() . PHP_EOL;
}
