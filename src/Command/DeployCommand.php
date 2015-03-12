<?php
namespace Berk\Command;

use Berk\Command;
use Berk\Ftp;
use Berk\Git;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeployCommand extends Command
{

    protected function configure()
    {
        $this->setName("deploy");
        $this->addArgument("server", InputArgument::REQUIRED);
        $this->addOption("commit", "c", InputOption::VALUE_REQUIRED, "Hash to use as remote reference point.");
        $this->addOption("dry", "d", InputOption::VALUE_NONE, "Dry run. Does not modify any files.");
        $this->addOption("remove", "r", InputOption::VALUE_NONE, "Remove remote files that don't exist locally.");
        $this->addOption("backup", "b", InputOption::VALUE_NONE, "Backup remote files before overwriting them.");
        $this->addOption('uncommitted', 'u', InputOption::VALUE_NONE, 'Include uncommitted files.');
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $servers     = $this->getApplication()->getConfiguration()['servers'];
        $server      = $i->getArgument("server");
        $dry         = $i->getOption("dry");
        $backup      = $i->getOption("backup");
        $remove      = $i->getOption("remove");
        $commit      = $i->getOption("commit");
        $uncommitted = $i->getOption('uncommitted');

        if($dry) {
            $o->writeln('<question>Dry run. No files will be modified.</question>');
            $o->setVerbosity(5);
        }

        if (!isset($servers[$server])) {
            $o->writeln("<error>Configuration for server '" . $server . "' not found.</error>");

            return;
        }

        $ftp        = new Ftp($servers[$server]["host"], $servers[$server]["port"], $servers[$server]["username"], $servers[$server]["password"], $servers[$server]["path"]);
        $rev_remote = $commit ? $commit : $ftp->getRevision();
        $rev_local  = Git::getCurrentRevision();

        $o->writeln("<info>Deploying to server: {$server}</info>");

        if ($o->isVerbose() || $dry) $o->writeln("<comment>- Local revision: " . $rev_local . "</comment>");
        if ($o->isVerbose() || $dry) $o->writeln("<comment>- Remote revision: " . $rev_remote . "</comment>");

        list($modified, $deleted) = $this->getFiles($rev_remote, $rev_local, $uncommitted);

        if (empty($modified) && empty($deleted)) {
            $o->writeln("<info>Server is up to date.</info>");

            return;
        }

        if ($remove && !empty($deleted)) {
            $o->writeln("<info>Removing files from server.</info>");
            if (!$dry) $ftp->log("[REV REMOVE][START] {$rev_remote} > {$rev_local}");

            $wdir    = Git::getWorkingDirectory();
            $process = function ($path) use ($ftp, $wdir) {
                $path_remote = str_replace($wdir, "", $path);
                $path_remote = ltrim($path_remote, "/");

                return $ftp->delete($path_remote);
            };

            $this->processFiles($deleted, $process, $dry, $o);

            if (!$dry) $ftp->log("[REV REMOVE][END] {$rev_remote} > {$rev_local}");
        }

        if (!empty($modified)) {
            $o->writeln("<info>Uploading modified files to server.</info>");
            if (!$dry) $ftp->log("[REV UPLOAD][START] {$rev_remote} > {$rev_local}");

            $wdir    = Git::getWorkingDirectory();
            $process = function ($path) use ($ftp, $wdir, $backup) {
                $path_remote = str_replace($wdir, "", $path);
                $path_remote = ltrim($path_remote, "/");

                $ftp->upload($path, $path_remote, $backup);
            };

            $this->processFiles($modified, $process, $dry, $o);

            if (!$dry) $ftp->log("[REV UPLOAD][END] {$rev_remote} > {$rev_local}");
        }

        if (!$dry) {
            $ftp->log("[REV UPLOAD][STOP] {$rev_remote} > {$rev_local}");
            $ftp->setRevision($rev_local);
            $o->writeln("<info>Remote revision updated.</info>");
        }
    }
}