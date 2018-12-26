<?php
declare(strict_types=1);

namespace MyApp\Contents;

use Selami\Interfaces\ApplicationController;
use Selami\ControllerResponse;

class Redirected extends ContentsController implements ApplicationController
{
    public function __invoke() : ControllerResponse
    {
        $serverParams = $this->request->getServerParams();
        return ControllerResponse::HTML(
            200,
            [
                't' => self::class,
                'referer' => $serverParams['HTTP_REFERER'] ?? ''
            ]
        );
    }
}
