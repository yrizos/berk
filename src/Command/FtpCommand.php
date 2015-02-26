<?php

namespace Berk\Command;

use Berk\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

abstract class FtpCommand extends Command
{
    private $config = array();

    protected function readConfiguration(InputInterface $i, OutputInterface $o, $server = 'development')
    {
        $file = getcwd() . '/.berk-ftp.json';

        if (!is_file($file)) {
            $host   = $port = $user = $pass = $path = "";
            $helper = $this->getHelper('question');

            while (empty($host)) $host = $helper->ask($i, $o, new Question("Host? "));
            while (empty($port)) $port = $helper->ask($i, $o, new Question("Port? (21) ", 21));
            while (empty($user)) $user = $helper->ask($i, $o, new Question("Username? "));
            while (empty($pass)) $pass = $helper->ask($i, $o, new Question("Password? "));
            while (empty($path)) $path = $helper->ask($i, $o, new Question("Remote path? "));

            $config = [
                'host'     => $host,
                'port'     => $port,
                'username' => $user,
                'password' => $pass,
                'path'     => $path
            ];

            $config = array_map('trim', $config);
            $config = [$server => $config];
            $config = json_encode($config);

            file_put_contents($file, $config);

            $o->writeln("<fg=green>Configuration saved in {$file}</fg=green>");
        }

        $this->config = json_decode(file_get_contents($file), true);
    }

    protected function getServerConfig($server)
    {
        if (!isset($this->config[$server])) throw new \ErrorException("Couldn't find configuration for server {$server}.");

        return $this->config[$server];
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function writeServerConfig($server, array $config, OutputInterface $o)
    {
        $table = new Table($o);

        $table->addRow(["<fg=green>server</fg=green>", "<fg=green>{$server}</fg=green>"]);

        unset($config['password']);
        foreach ($config as $key => $value) {
            $table->addRow([$key, $value]);
        }

        $table->render();
        $o->writeln('');
    }
}