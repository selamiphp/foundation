<?php
declare(strict_types=1);

namespace MyApp\Contents;

use Selami\ControllerResponse;
use Selami\Interfaces\Controller;

class CamelCase extends ContentsController implements Controller
{
    public function __invoke() : ControllerResponse
    {
        return ControllerResponse::HTML(
            200,
            [
                't' => self::class
            ]
        );
    }
}
