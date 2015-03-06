<?php

namespace Berk\Command;

use Berk\Command;
use Berk\Git;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends Command
{
    protected function configure()
    {
        $this->setName("export");

        $this->addOption('from', 'f', InputOption::VALUE_REQUIRED, 'Export will start from this revision', Git::getPreviousRevision());
        $this->addOption('to', 't', InputOption::VALUE_REQUIRED, 'Export will end at this revision', Git::getCurrentRevision());
        $this->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'Export directory', Git::getWorkingDirectory() . '/.berk');
        $this->addOption('uncommitted', 'u', InputOption::VALUE_NONE, 'Include uncommitted files.');
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $from       = $i->getOption('from');
        $to         = $i->getOption('to');
        $dir        = $i->getOption('dir');
        $uncommitted = $i->getOption('uncommitted');
        $dir        = str_replace(["\\", "/"], "/", $dir);
        $dir        = rtrim($dir, "/");

        if ($from == $to) {
            $o->writeln("<info>Exporting revision {$from}</info>");
        } else {
            $o->writeln("<info>Exporting revisions {$from} to {$to}.</info>");
        }

        list($modified, $deleted) = $this->getFiles($from, $to, $uncommitted);
        $files = $modified;

        if (!is_dir($dir)) {
            $o->writeln("<comment>- Creating export directory {$dir}.</comment>");

            mkdir($dir, 0777, true);
        } else {
            $o->writeln("<comment>- Emptying export directory {$dir}.</comment>");

            self::rmDir($dir);
        }

        if(!is_dir($dir)) throw new \Exception();

        $wdir    = Git::getWorkingDirectory();
        $process = function ($path) use ($dir, $wdir) {
            $path_partial = str_replace($wdir . '/', '', $path);
            $path_export  = $dir . '/' . $path_partial;
            $dirname      = dirname($path_export);

            if (!is_dir($dirname)) mkdir($dirname, 0777, true);

            return copy($path, $path_export);
        };

        $o->writeln("<info>Exporting files</info>");
        $this->processFiles($files, $process, false, $o);
    }

    public static function rmDir($dir)
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
    }

}