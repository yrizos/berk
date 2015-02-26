<?php

namespace Berk\Command;

use Berk\Git;
use Berk\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GitInfoCommand extends Command
{

    protected function configure()
    {
        $this->setName('git:info');
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $info = [
            'version'     => Git::getVersion(),
            'branch'      => Git::getCurrentBranch(),
            'w. dir'      => Git::getWorkingDirectory(),
            'last commit' => Git::getLastCommit(),
        ];

        $table = new Table($o);

        foreach ($info as $key => $value) {
            if ($key == 'branch' && $value != 'master') $value = $this->color($value, 'red');

            $key = $this->color($key, 'green');

            $table->addRow([$key, $value]);
        }

        $table->render();

        $messages = [];
        if (!Git::isUpdated()) {
            $messages[] = $this->color("There are uncommited changes.", "red");
        } else {
            $messages[] = "Everything is up to date.";
        }

        if (!Git::inWorkingDirectory()) {
            $messages[] = $this->color("You are not in the repository's working directory.", "red");
        } else {
            $messages[] = "You are in the repository's working directory.";
        }

        foreach ($messages as $message) $o->writeln($message);

    }
}