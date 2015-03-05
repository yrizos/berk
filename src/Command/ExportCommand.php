<?php

namespace Berk\Command;

use Berk\Command;
use Berk\Git;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends Command
{
    protected function configure()
    {
        $this->setName("export");

        $this->addArgument('dir', InputArgument::OPTIONAL, 'Export directory', Git::getWorkingDirectory() . '/.berk');
        $this->addArgument('from', InputArgument::OPTIONAL, 'Export will start from this revision', Git::getCurrentRevision());
        $this->addArgument('to', InputArgument::OPTIONAL, 'Export will end at this revision', Git::getCurrentRevision());
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $from = $i->getArgument('from');
        $to   = $i->getArgument('to');
        $dir  = $i->getArgument('dir');
        $dir  = str_replace(["\\", "/"], "/", $dir);
        $dir  = rtrim($dir, "/");

        if ($from == $to) {
            $o->writeln("<info>Exporting revision {$from}</info>");
        } else {
            $o->writeln("<info>Exporting revisions {$from} to {$to}.</info>");
        }

        $files = Git::getRevisionFilesBetween($from, $to);
        $files = array_filter($files, function ($path) {
            return is_file($path);
        });

        if (empty($files)) {
            $o->writeln("<info>No modified files found.</info>");

            return;
        }

        if (!is_dir($dir)) {
            $o->writeln("<comment>- Creating export directory {$dir}.</comment>");

            mkdir($dir, 0777, true);
        } else {
            $o->writeln("<comment>- Emptying export directory {$dir}.</comment>");

            self::rmDir($dir);
        }

        $o->writeln("<comment>- Copying " . count($files) . " files.</comment>");

        $wdir = Git::getWorkingDirectory();
        foreach ($files as $path) {
            $path_partial = str_replace($wdir . '/', '', $path);
            $path_export  = $dir . '/' . $path_partial;
            $dirname      = dirname($path_export);

            if (!is_dir($dirname)) mkdir($dirname, 0777, true);

            copy($path, $path_export);
        }

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