<?php
declare(strict_types=1);

namespace MyApp\Contents;

use Selami\Interfaces\Controller;
use Selami\ControllerResponse;

class Post extends ContentsController implements Controller
{
    public function __invoke() : ControllerResponse
    {
        /*$count = $this->session->get('count', 0);
        $count++;
        $this->session->set('count', $count);*/
        $count = 1;
        return ControllerResponse::JSON(
            200,
            [
                'controller-class' => self::class,
                'uri-parameters' => $this->uriParameters,
                'session-count-value' => $count
            ]
        );
    }
}
