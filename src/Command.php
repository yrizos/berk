<?php

namespace Berk;

use Symfony\Component\Console\Command\Command as SymfonyCommand;

abstract class Command extends SymfonyCommand
{

    /**
     * @return \Berk\Application
     */
    public function getApplication()
    {
        return parent::getApplication();
    }
}