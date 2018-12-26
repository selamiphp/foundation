<?php
declare(strict_types=1);

namespace MyApp\Contents;

use Selami\Interfaces\ApplicationController;
use Selami\ControllerResponse;

class Custom extends ContentsController implements ApplicationController
{
    public function __invoke() : ControllerResponse
    {
        return ControllerResponse::CUSTOM(
            200,
            [
                't' => 'Custom',
                'name' => 'Mırmır',
                'last_name' => 'Kedigil',
                'age' => 10
            ],
            [],
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=custom.csv'
            ]
        );
    }
}
