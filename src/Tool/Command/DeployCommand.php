<?php

namespace Berk\Tool\Command;

use Berk\Git;
use Berk\Tool\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends Command
{

    protected function configure()
    {
        $this->setName('deploy');
        $this->addArgument('server', InputArgument::REQUIRED, 'Server name');
        $this->addOption('dry', 'd', InputOption::VALUE_NONE, 'Dry run');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'Deploy all files');
        $this->addOption('backup', 'b', InputOption::VALUE_NONE, 'Backup online files');
        $this->addOption('uncommited', 'u', InputOption::VALUE_NONE, 'Deploy will include uncommited files');
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $server     = $i->getArgument('server');
        $dry        = $i->getOption('dry') === true;
        $all        = $i->getOption('all') === true;
        $backup     = $i->getOption('backup') === true;
        $uncommited = $all ? true : $i->getOption('uncommited') === true;

        $o->writeln('<info>Connecting to server: ' . $server . '</info>');
        $connection = $this->getBerk()->getFtpConnection($server);

        $revision_local  = Git::getCurrentRevision();
        $revision_remote = $connection->getRevision();
        $revision_from   = $all ? null : $revision_remote;
        $revision_to     = $all ? null : $revision_local;

        $o->writeln('<comment>Server revision: ' . $revision_remote . '</comment>');
        $o->writeln('<comment>Local revision: ' . $revision_local . '</comment>');

        $files    = $this->getFiles($revision_from, $revision_to, $uncommited);
        $modified = $removed = [];

        foreach ($files as $path) {
            if (is_file($path)) {
                $modified[] = $path;
            } else {
                $removed[] = $path;
            }
        }

        $count_modified = count($modified);
        $count_removed  = count($removed);

        if ($dry) {
            $o->writeln('<info>Modified files: ' . $count_modified . '</info>');

            foreach ($modified as $path) $o->writeln($path);

            $o->writeln('<info>Removed files: ' . $count_removed . '</info>');

            foreach ($removed as $path) $o->writeln($path);

            return;
        }

        $working_dir = $this->getBerk()->getDirectory();

        if ($count_removed > 0) {
            $connection->log("[REV REMOVE][START] {$revision_remote} > {$revision_local} [{$count_removed}]");

            $this->processFiles(
                'Removing obsolete files',
                $removed,
                function ($source) use ($working_dir, $connection) {
                    $target = str_replace($working_dir, '', $source);
                    $target = ltrim($target, DIRECTORY_SEPARATOR);

                    $connection->delete($target);

                    return true;
                }
            );

            $connection->log("[REV REMOVE][END] {$revision_remote} > {$revision_local}");
        }

        if ($count_modified > 0) {
            $connection->log("[REV UPLOAD][START] {$revision_remote} > {$revision_local} [{$count_modified}]");

            $result = $this->processFiles(
                'Uploading modified files',
                $modified,
                function ($source) use ($working_dir, $connection, $backup) {
                    $target = str_replace($working_dir, '', $source);
                    $target = ltrim($target, DIRECTORY_SEPARATOR);

                    $connection->upload($source, $target, $backup);

                    return true;
                }
            );

            $connection->log("[REV UPLOAD][END] {$revision_remote} > {$revision_local}");
        }

        $connection->setRevision($revision_local);
    }


}