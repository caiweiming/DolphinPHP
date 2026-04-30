<?php
declare(strict_types=1);

use think\facade\Route;

Route::get('plugin/demo/hello/ping', '\\Plugins\\Demo\\Hello\\Controller\\IndexController@ping')
    ->middleware('demo_hello_trace')
    ->completeMatch();
Route::get('plugin/demo/hello', '\\Plugins\\Demo\\Hello\\Controller\\IndexController@index')
    ->completeMatch();
