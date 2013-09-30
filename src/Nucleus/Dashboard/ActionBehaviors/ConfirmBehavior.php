<?php

namespace Nucleus\Dashboard\ActionBehaviors;

class ConfirmBehavior extends AbstractActionBehavior
{
    protected $params = array(
        'message' => 'Are you sure?'
    );

    public function getName()
    {
        return 'confirm';
    }
}