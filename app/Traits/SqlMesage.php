<?php

namespace App\Traits;

trait SqlMesage
{

    use Loggable;
    public function sqlMessageError($e)
    {
        $message =  explode('(Connection', explode('DETAIL:', $e->getMessage())[1])[0];
        $this->logError($message);
        return $message;
    }
}
