<?php

namespace Berk\Tool\Command;

use Berk\Git;
use Berk\Tool\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerCommand extends Command
{

    protected function configure()
    {
        $this->setName('server');
        $this->addArgument('server', InputArgument::REQUIRED, 'Server name');
        $this->addOption('set-revision', 'r', InputOption::VALUE_REQUIRED);
        $this->addOption('log', 'l', InputOption::VALUE_OPTIONAL, 'Show log entries', 50);
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $server       = $i->getArgument('server');
        $set_revision = $i->getOption('set-revision');
        $show_log     = (int) $i->getOption('log');

        if ($set_revision) {
            $revisions = Git::getRevisions();
            if (!in_array($set_revision, $revisions)) {
                $o->writeln('<error>Revision ' . $set_revision . ' is invalid</error>');

                return;
            }
        }


        $o->writeln('<info>Connecting to server: ' . $server . '</info>');
        $connection = $this->getBerk()->getFtpConnection($server);

        if ($set_revision) {
            $connection->setRevision($set_revision);
            $o->writeln('<info>Server revision updated: ' . $set_revision . '</info>');

            return;
        }

        if ($show_log) {
            $log = $connection->getLog();
            $log = array_slice($log, -$show_log);

            foreach($log as $line) $o->writeln($line);

            return;
        }

        $revision_local  = Git::getCurrentRevision();
        $revision_remote = $connection->getRevision();

        $o->writeln('<comment>Server revision: ' . $revision_remote . '</comment>');
        $o->writeln('<comment>Local revision: ' . $revision_local . '</comment>');
    }

}