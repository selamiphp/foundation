<?php
declare(strict_types=1);

namespace MyApp\Contents;

use Selami\Interfaces\Controller;
use Selami\ControllerResponse;

class Download extends ContentsController implements Controller
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
