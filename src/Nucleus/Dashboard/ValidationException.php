<?php

namespace Nucleus\Dashboard;

use Symfony\Component\Validator\ConstraintViolationList;

class ValidationException extends DashboardException
{
    private $violiations;

    public function __construct($violiations)
    {
        $this->violiations = $violiations;
    }

    public function getVioliations()
    {
        return $this->violiations;
    }
}
