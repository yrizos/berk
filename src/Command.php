<?php

namespace Berk;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SymfonyCommand
{

    protected function getGitInfo()
    {
        return [
            'version' => Git::getVersion(),
            'branch'  => Git::getCurrentBranch(),
            'w. dir'  => Git::getWorkingDirectory(),
        ];
    }

    protected function writeGitInfo(OutputInterface $o)
    {
        $table    = new Table($o);
        $info     = $this->getGitInfo();
        $messages = array();

        if (!Git::isUpdated()) {
            $messages[] = "There are uncommited changes";
        }

        if ($info['w. dir'] != getcwd()) {
            $messages[] = "You are not on the working directory";
        }

        $col = 0;
        foreach ($info as $key => $value) {
            $table->setRow($col, ["<fg=green>{$key}</fg=green>", $value]);
            $col++;
        }

        $table->render();

        foreach ($messages as $message) {
            $o->writeln("<fg=red>* {$message}</fg=red>");
        }
    }
}