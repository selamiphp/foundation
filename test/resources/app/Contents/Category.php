<?php
declare(strict_types=1);


namespace MyApp\Contents;

use Selami\Interfaces\Controller;
use Selami\ControllerResponse;

class Category extends ContentsController implements Controller
{
    public function __invoke() : ControllerResponse
    {
        return ControllerResponse::HTML(
            200,
            [
                't' => self::class,
                'uriParameter' => $this->uriParameters['category']
            ]
        );
    }
}
