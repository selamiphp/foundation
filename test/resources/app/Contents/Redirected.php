<?php
declare(strict_types=1);

namespace MyApp\Contents;

use Selami\Interfaces\Controller;
use Selami\ControllerResponse;

class Redirected extends ContentsController implements Controller
{
    public function __invoke() : ControllerResponse
    {
        return ControllerResponse::HTML(
            200,
            [
                't' => self::class,
                'referer' => $this->request->getServerParams()['HTTP_REFERER']
            ]
        );
    }
}
