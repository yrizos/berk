<?php

namespace Berk\Tool;

use Berk\Berk;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class Command extends SymfonyCommand
{
    /** @var Berk */
    private $berk;

    /**
     * @param Berk $berk
     * @return $this
     */
    public function setBerk(Berk $berk)
    {
        $this->berk = $berk;

        return $this;
    }

    /**
     * @return Berk
     */
    public function getBerk()
    {
        return $this->berk;
    }

    /**
     * @param null $revision_from
     * @param null $revision_to
     * @param bool $uncommited
     * @return array
     */
    public function getFiles($revision_from = null, $revision_to = null, $uncommited = false)
    {
        return
            empty($revision_from) && empty($revision_from)
                ? $this->getBerk()->getAllFiles()
                : $this->getBerk()->getFilesBetween($revision_from, $revision_to, $uncommited);
    }

    public function processFiles($title, array $files, $process)
    {
        $o = new ConsoleOutput();
        $o->writeln('<info>' . $title . '</info>');

        $p = new ProgressBar($o, count($files));
        $p->start();

        foreach ($files as $path) {
            $process($path);

            $p->advance();
        }

        $p->finish();
        $o->writeln('');
    }
}