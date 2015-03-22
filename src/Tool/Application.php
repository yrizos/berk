<?php
namespace Berk\Tool;

use Berk\Berk;
use Berk\Git;
use Berk\Tool\Command\DeployCommand;
use Berk\Tool\Command\ExportCommand;
use Berk\Tool\Command\InfoCommand;
use Berk\Tool\Command\ServerCommand;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Application extends SymfonyApplication
{
    public function __construct()
    {
        parent::__construct(Berk::NAME, Berk::VERSION);

        $time       = time();
        $dispatcher = new EventDispatcher();

        $dispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
            $output    = $event->getOutput();
            $directory = Git::getWorkingDirectory();

            chdir($directory);
            $output->writeln('<info>Changed current directory to: ' . $directory . '</info>');
            $output->writeln('');

            $command = $event->getCommand();
            if ($command instanceof Command) $command->setBerk(new Berk());
        });

        $dispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) use ($time) {
            $output = $event->getOutput();
            $time   = time() - $time;

            $output->writeln('');
            $output->writeln('<info>Command completed in ' . $time . ' seconds. Bye.</info>');
        });

        $this->setDispatcher($dispatcher);

        $this->add(new ServerCommand());
        $this->add(new ExportCommand());
        $this->add(new DeployCommand());
    }

}