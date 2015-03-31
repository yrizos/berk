<?php

namespace Berk\Tool\Command;

use Berk\Berk;
use Berk\Git;
use Berk\Tool\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends Command
{

    protected function configure()
    {
        $this->setName('export');
        $this->addArgument('directory', InputArgument::OPTIONAL, 'Export root directory', Git::getWorkingPath(Berk::EXPORT_DIRECTORY));
        $this->addOption('from', 'f', InputOption::VALUE_REQUIRED, 'Export will start from this revision');
        $this->addOption('to', 't', InputOption::VALUE_REQUIRED, 'Export will end at this revision');
        $this->addOption('uncommited', 'u', InputOption::VALUE_NONE, 'Export will include uncommited files');
        $this->addOption('zip', 'z', InputOption::VALUE_NONE, 'Zip export');
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $revision_from = $i->getOption('from');
        $revision_to   = $i->getOption('to');
        $uncommited    = $i->getOption('uncommited') === true;
        $zip           = $i->getOption('zip') === true;

        $o->writeln('<info>Discovering files</info>');
        $files = $this->getFiles($revision_from, $revision_to, $uncommited);
        $files = array_filter($files, function($file) { return file_exists($file); });
        $count = count($files);

        if ($count < 1) {
            $o->writeln('<comment>No files found</comment>');

            return;
        }

        $working_dir = $this->getBerk()->getDirectory();
        $export_dir  = $i->getArgument('directory') . DIRECTORY_SEPARATOR . $this->getBerk()->getConfiguration()['name'];
        $export_dir .= empty($revision_from) ? '-0' : '-' . $revision_from;
        $export_dir .= empty($revision_to) ? '-0' : '-' . $revision_to;
        $export_dir .= '-' . time();

        if ($zip) {
            $export_path = $export_dir . '.zip';
            $export_dir  = dirname($export_path);

            if (!is_dir($export_dir)) mkdir($export_dir, 0777, true);

            $zip = new \ZipArchive();
            $zip->open($export_path, \ZipArchive::CREATE);

            $this->processFiles(
                'Archiving files',
                $files,
                function ($source) use ($working_dir, $zip) {
                    $target = str_replace($working_dir, '', $source);
                    $target = str_replace("\\", '/', $target);
                    $target = ltrim($target, '/');

                    return $zip->addFile($source, $target);
                }
            );

            $zip->close();

            $o->writeln('<info>Export path: ' . $export_path);
        } else {
            $this->processFiles(
                'Exporting files',
                $files,
                function ($source) use ($export_dir, $working_dir) {
                    $target  = str_replace($working_dir, $export_dir, $source);
                    $dirname = dirname($target);

                    if (!is_dir($dirname)) @mkdir($dirname, 0777, true);
                    if (!is_dir($dirname)) return false;

                    return copy($source, $target);
                }
            );

            $o->writeln('<info>Export path: ' . $export_dir);
        }
    }

}