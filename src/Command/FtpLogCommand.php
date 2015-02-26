<?php

namespace Berk\Command;

use Berk\Ftp;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FtpLogCommand extends FtpCommand {

    protected function configure()
    {
        $this->setName('ftp:log');

        $this->addArgument('server', InputArgument::REQUIRED, 'server name');
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $this->readConfiguration($i, $o);

        $config = $this->getServerConfig()

        foreach ($config as $server => $c) {
            $ftp = new Ftp($c['host'], $c['port'], $c['username'], $c['password'], $c['path']);

            $ftp->setRevision($server . time());

            $c['revision'] = $ftp->getRevision();

            $this->writeServerConfig($server, $c, $o);
        }
    }

}