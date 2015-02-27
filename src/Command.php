<?php

namespace Berk;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SymfonyCommand
{

    /**
     * @return \Berk\Berk
     */
    public function getApplication()
    {
        return parent::getApplication();
    }

    protected function color($msg, $color)
    {
        $msg = trim($msg);

        return "<fg={$color}>" . trim($msg) . "</fg={$color}>";
    }

    protected function writeError($msg, OutputInterface $o = null)
    {
        if ($o === null) $o = new ConsoleOutput();

        $msg = $this->color($msg, 'red');
        $o->writeln($msg);

        return $msg;
    }

    protected function getGitInfo()
    {
        return [
            'version' => Git::getVersion(),
            'branch'  => Git::getCurrentBranch(),
            'w. dir'  => Git::getWorkingDirectory(),
        ];
    }
}