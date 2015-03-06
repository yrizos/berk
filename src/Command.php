<?php

namespace Berk;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SymfonyCommand
{

    /**
     * @return \Berk\Application
     */
    public function getApplication()
    {
        return parent::getApplication();
    }

    protected function getFiles($from, $to, $uncommitted = false)
    {
        $exclude = $this->getApplication()->getConfiguration()['exclude'];
        $files   = ($uncommitted === true) ? Git::getuncommittedFiles() : [];
        $files   = $files + Git::getRevisionFilesBetween($from, $to);
        $files   = array_unique($files);
        $files   = array_filter($files, function ($path) use ($exclude) {
            foreach ($exclude as $x) if (strpos($path, $x) === 0) return false;

            return true;
        });

        $modified = $deleted = [];
        foreach ($files as $path) {
            if (is_file($path)) {
                $modified[] = $path;
            } else {
                $deleted[] = $path;
            }
        }

        return [$modified, $deleted];
    }

    protected function processFiles(array $files, $process, $dry = false, OutputInterface $o = null)
    {
        if (!is_callable($process)) throw new \Exception();
        if (null == $o) $o = new ConsoleOutput();

        $dry = ($dry === true);

        if (empty($files)) {
            $o->writeln("<comment>- There are no files to process.</comment>");

            return;
        }

        sort($files);

        $count = count($files);
        foreach ($files as $index => $path) {
            $index++;

            if ($o->isVerbose()) $o->writeln("<comment>- [{$index}/{$count}] {$path}</comment>");

            if(!$dry) $process($path);
        }

        if (!$o->isVerbose()) $o->writeln("<comment>- {$count} files were processed.</comment>");
    }
}