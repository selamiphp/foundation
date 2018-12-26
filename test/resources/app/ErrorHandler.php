<?php
declare(strict_types=1);

namespace MyApp;

use Selami\ControllerResponse;
use Selami\Interfaces\ApplicationController;

class ErrorHandler implements ApplicationController
{
    private $status;
    private $message;

    public function __construct(int $status, string $message)
    {
        $this->status = $status;
        $this->message = $message;
    }

    public function __invoke(): ControllerResponse
    {
        return ControllerResponse::HTML(
            $this->status,
            $data = [
                'status' => $this->status,
                'message' => $this->message,
            ],
            $metaData = [
                'layout' => 'error_handler'
            ]
        );
    }
}
