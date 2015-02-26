<?php

namespace Berk;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class Command extends SymfonyCommand
{

    /**
     * @return \Berk\Berk
     */
    public function getApplication() {
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

    protected function askConfirmation(InputInterface $i, OutputInterface $o)
    {
        if (!Git::inWorkingDirectory()) {
            $o->writeln("<fg=red>You are not in the repository's working directory.</fg=red>");

            return false;
        }

        if (!Git::isUpdated()) {
            $helper   = $this->getHelper('question');
            $question = new ConfirmationQuestion('<fg=yellow>There are uncommited changes. Continue?</fg=yellow> (y/N) ', false);

            return $helper->ask($i, $o, $question);
        }

        return true;
    }

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
            $messages[] = "There are uncommited changes.";
        }

        if (!Git::inWorkingDirectory()) {
            $messages[] = "You are not in the repository's working directory.";
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