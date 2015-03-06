<?php

namespace Berk;

use Berk\Command\ConfigCommand;
use Berk\Command\DeployCommand;
use Berk\Command\InfoCommand;
use Berk\Command\ExportCommand;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\ConsoleEvents;
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

        $this->setDispatcher($dispatcher);

        $this->add(new InfoCommand());
        $this->add(new ConfigCommand());
        $this->add(new DeployCommand());
        $this->add(new ExportCommand());
    }

    private function readConfiguration()
    {
        $config  = $this->getConfigurationPath();
        $config  = is_file($config) ? @json_decode(file_get_contents($config), true) : [];
        $servers = isset($config['servers']) && is_array($config['servers']) ? $config['servers'] : [];
        $exclude = isset($config['exclude']) && is_array($config['exclude']) ? $config['exclude'] : [];

        $exclude = array_map(function ($path) {
            return Git::getWorkingPath($path);
        }, $exclude);

        $exclude = array_filter($exclude, function ($path) {
            return file_exists($path);
        });

        ksort($servers);

        $exclude = array_unique($exclude);
        sort($exclude);

        $config["servers"] = $servers;
        $config["exclude"] = $exclude;

        return $config;
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

    public function getFiles($revision_from, $revision_to)
    {

    }


}