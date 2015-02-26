<?php

namespace Berk\Command;

use Berk\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ConfigCommand extends Command
{
    protected function configure()
    {
        $this->setName('config');
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $path   = $this->getApplication()->getConfigurationPath();
        $config = $this->getApplication()->getConfiguration();

        if (!empty($config)) {
            $o->writeln($this->color("Configuration read from {$path}", "green"));


            foreach ($config as $server => $c) {
                unset($c['password']);

                $table = new Table($o);
                $table->addRow([$this->color('server', 'green'), $this->color($server, 'green')]);

                foreach ($c as $key => $value) $table->addRow([$key, $value]);

                $o->writeln('');
                $table->render();
            }

            return;
        }
        $helper = $this->getHelper('question');
        $add    = $helper->ask($i, $o, new ConfirmationQuestion("Create configuration? (Y/n) ", true));

        if (!$add) return;

        $config = [];
        while ($add) {
            $o->writeln('');

            $server = $host = $port = $user = $pass = $path = "";
            $helper = $this->getHelper('question');

            while (empty($server)) $server = $helper->ask($i, $o, new Question("Server name? (dev) ", "dev"));
            while (empty($host)) $host = $helper->ask($i, $o, new Question("Host? "));
            while (empty($port)) $port = $helper->ask($i, $o, new Question("Port? (21) ", 21));
            while (empty($user)) $user = $helper->ask($i, $o, new Question("Username? "));
            while (empty($pass)) $pass = $helper->ask($i, $o, new Question("Password? "));
            while (empty($path)) $path = $helper->ask($i, $o, new Question("Remote path? (/) ", "/"));

            $server          = trim($server);
            $config[$server] = [
                'host'     => $host,
                'port'     => $port,
                'username' => $user,
                'password' => $pass,
                'path'     => $path
            ];

            $o->writeln('');

            $add = $helper->ask($i, $o, new ConfirmationQuestion("Add another? (y/N) ", false));
        }

        ksort($config);

        foreach ($config as $k => $v) {
            ksort($v);
            $config[$k] = array_map('trim', $v);
        }


        $config = json_encode($config);

        file_put_contents($path, $config);

        $o->writeln($this->color("Configuration saved in {$path}", "green"));
    }
}