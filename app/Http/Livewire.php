<?php


namespace App\Http;


use Livewire\LifecycleManager;
use Luracast\Restler\MediaTypes\Custom;

class Livewire
{
    /**
     * @param string $command {@from path}
     * @param $request_body
     * @return mixed
     *
     * @class Html {@template manual}
     * @response-format Html
     */
    public function postMessage(string $command, $request_body)
    {

        return $this->handle($request_body);
    }

    private function handle($payload)
    {
        return json_encode(LifecycleManager::fromSubsequentRequest($payload)
            ->hydrate()
            ->renderToView()
            ->dehydrate()
            ->toSubsequentResponse());
    }

}
