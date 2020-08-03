<?php
/**
 * Laravel Database - for any web application
 *
 * @package  Database
 * @author   Arul Kumaran <arul@luracast.com>
 */

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/

use Luracast\Restler\OpenApi3\Explorer;
use App\Http\Controllers\Home;
use Luracast\Restler\Restler;
use Luracast\Restler\Router;

require __DIR__ . '/../bootstrap/autoload.php';

/*
|--------------------------------------------------------------------------
| Configure your Web Application
|--------------------------------------------------------------------------
|
| Configure your favourite web app framework to handle web requests and
| respond back. If you are using Restler 4 framework, you may simply uncomment
| the code below and run the following command from the command line on the
| project root folder
|
|    composer require arul/reactphp-restler
|
*/

try {
    Router::mapApiClasses([
        '' => Home::class,
        Explorer::class
    ]);
} catch (Throwable $throwable) {
    echo $throwable->getMessage() . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application set up, we can simply let it handle the
| request and response
|
*/

$r = new Restler();
$r->handle();
