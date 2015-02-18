<?php
namespace Berk\Command;

use Berk\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends Command
{

    protected function configure()
    {
        $this->setName('info');
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $this->writeGitInfo($o);
    }

}