<?php


namespace App\Http\Controllers;


class Home
{
    /**
     * @return object[]
     *
     * @response-format Html
     */
    public function index()
    {
        return ['success' => ['code' => 200, 'message' => 'Restler is up and running!']];
    }
}
