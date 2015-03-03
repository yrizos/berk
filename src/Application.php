<?php

namespace Berk;

use Berk\Command\ConfigCommand;
use Berk\Command\DeployCommand;
use Berk\Command\InfoCommand;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;


class Application extends SymfonyApplication
{
    const NAME = 'berk';
    const VERSION = '0.0.1';
    const CONFIGURATION = '.berk.json';

    private $configuration = [];

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $time       = time();
        $dispatcher = new EventDispatcher();

        $dispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) use ($time) {
            $output = $event->getOutput();
            $time   = time() - $time;

            $output->writeln('');
            $output->writeln('<info>Command completed in ' . $time . ' seconds. Bye.</info>');

        });

        $dispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
            $output      = $event->getOutput();
            $command     = $event->getCommand();

            if (!Git::isUpdated()) {
                $output->writeln('<error>There are uncommited changes.</error>');

                if ($command instanceof DeployCommand) {
                    $event->disableCommand();
                }
            }
        });

        $this->setDispatcher($dispatcher);

        $this->add(new InfoCommand());
        $this->add(new ConfigCommand());
        $this->add(new DeployCommand());
    }

    private function readConfiguration()
    {
        $configuration = $this->getConfigurationPath();
        $configuration = is_file($configuration) ? @json_decode(file_get_contents($configuration), true) : false;

        if (!is_array($configuration)) $configuration = [];
        if (!isset($configuration['servers'])) $configuration['servers'] = [];
        if (!isset($configuration['exclude'])) $configuration['exclude'] = [];

        return $configuration;
    }

    public function getConfigurationPath()
    {
        return Git::getWorkingDirectory() . '/' . self::CONFIGURATION;
    }

    public function getConfiguration()
    {
        if (empty($this->configuration)) $this->configuration = $this->readConfiguration();

        return $this->configuration;
    }


}