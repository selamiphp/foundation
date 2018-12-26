<?php
declare(strict_types=1);

namespace MyApp\Contents;

use Selami\Interfaces\ApplicationController;
use Selami\ControllerResponse;

class Download extends ContentsController implements ApplicationController
{
    public function __invoke() : ControllerResponse
    {
        return ControllerResponse::DOWNLOAD(
            200,
            './public/logo.png',
            'selami-logo.png'
        );
    }
}
