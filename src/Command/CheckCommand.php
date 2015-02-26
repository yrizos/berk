<?php
namespace Berk\Command;

use Berk\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command
{

    protected function configure()
    {
        $this->setName('check');
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $git_info = $this->getApplication()->getGitInfo();
        $table    = new Table($o);
        $messages = array();

        if (!Git::isUpdated()) $messages[] = $this->color("There are uncommited changes.", "red");
        if (!Git::inWorkingDirectory()) $messages[] = $this->color("You are not in the repository's working directory.", "red");

        foreach($git_info as $key => $value) $table->addRow([$this->color($key, "yellow"), $this->col])

        $col = 0;
        foreach ($info as $key => $value) {
            $table->setRow($col, ["<fg=green>{$key}</fg=green>", $value]);
            $col++;
        }

        $table->render();

        foreach ($messages as $message) {
            $o->writeln("<fg=red>* {$message}</fg=red>");
        }

        var_dump($git_info);


    }

}