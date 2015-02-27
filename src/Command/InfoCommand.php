<?php
namespace Berk\Command;

use Berk\Ftp;
use Berk\Git;
use Berk\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends Command
{

    protected function configure()
    {
        $this->setName('info');
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $git_info = $this->getApplication()->getGitInfo();
        $table    = new Table($o);
        $messages = array();

        if (!Git::isUpdated()) $messages[] = $this->color("There are uncommited changes.", "red");
        if (!Git::inWorkingDirectory()) $messages[] = $this->color("You are not in the repository's working directory.", "red");

        foreach ($git_info as $key => $value) $table->addRow([$this->color($key, "yellow"), $value]);

        $table->render();

        foreach ($messages as $message) $o->writeln($message);

        $config = $this->getApplication()->getConfiguration();

        if (empty($config)) {
            $this->writeError("No configuration found in " . Git::getWorkingDirectory());

            return;
        }

        foreach ($config as $key => $value) {
            $ftp      = new Ftp($value['host'], $value['port'], $value['username'], $value['password'], $value['path']);
            $revision = $ftp->getRevision();
            $commits  = Git::getAllCommitsSince($revision);
            $count    = count($commits);

            $o->writeln('');
            $o->writeln($this->color("[{$key}] ftp://{$value['username']}@{$value['host']}:{$value['port']}/{$value['path']}", "yellow"));

            $o->writeln("Revision: {$revision}");
            $o->writeln($count === 0 ? "Server is up to date." : "Server is {$count} revisions behind.");

        }

    }

}