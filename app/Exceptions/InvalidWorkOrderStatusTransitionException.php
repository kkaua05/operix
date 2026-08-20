<?php

namespace App\Exceptions;

use App\Enums\WorkOrderStatus;
use RuntimeException;

class InvalidWorkOrderStatusTransitionException extends RuntimeException
{
    public static function make(WorkOrderStatus $from, WorkOrderStatus $to): self
    {
        return new self("Não é possível mudar o status de \"{$from->label()}\" para \"{$to->label()}\".");
    }
}
