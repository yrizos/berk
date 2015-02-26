<?php

namespace Berk\Command;

use Berk\Ftp;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FtpCheckCommand extends FtpCommand
{

    protected function configure()
    {
        $this->setName('ftp:check');
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        if (!$this->askConfirmation($i, $o)) return false;

        $this->readConfiguration($i, $o);

        $config = $this->getConfig();

        foreach ($config as $server => $c) {
            $ftp = new Ftp($c['host'], $c['port'], $c['username'], $c['password'], $c['path']);

            $ftp->setRevision($server . time());

            $c['revision'] = $ftp->getRevision();

            $this->writeServerConfig($server, $c, $o);
        }
    }
}