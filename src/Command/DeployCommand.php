<?php
namespace Berk\Command;

use Berk\Command;
use Berk\Ftp;
use Berk\Git;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends Command
{

    protected function configure()
    {
        $this->setName("deploy");
        $this->addArgument("server", InputArgument::REQUIRED);
        $this->addOption("commit", "c", InputOption::VALUE_REQUIRED, "Hash to use as remote reference point.", false);
        $this->addOption("dry", "d", InputOption::VALUE_NONE, "Dry run. Does not modify any files.");
        $this->addOption("remove", "r", InputOption::VALUE_NONE, "Remove remote files that don't exist locally.");
        $this->addOption("backup", "b", InputOption::VALUE_NONE, "Backup remote files before overwriting them.");
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $config = $this->getApplication()->getConfiguration();
        $server = $i->getArgument("server");
        $dry    = $i->getOption("dry");
        $backup = $i->getOption("backup");
        $remove = $i->getOption("remove");
        $commit = $i->getOption("commit");

        if (!isset($config["servers"][$server])) {
            $o->writeln("<error>Configuration for server '" . $server . "' not found.</error>");

            return;
        }

        $ftp = new Ftp($config["servers"][$server]["host"], $config["servers"][$server]["port"], $config["servers"][$server]["username"], $config["servers"][$server]["password"], $config["servers"][$server]["path"]);

        $msg = "Deploying to server: " . $server . ".";
        if ($dry) $msg .= " (dry run)";

        $o->writeln("<info>" . $msg . "</info>");

        $rev_remote = $commit ? $commit : $ftp->getRevision();
        $rev_local  = Git::getLastCommit();

        if ($o->isVerbose()) $o->writeln("<comment>- Local revision: " . $rev_local . "</comment>");
        if ($o->isVerbose()) $o->writeln("<comment>- Remote revision: " . $rev_remote . "</comment>");

        if ($rev_local === $rev_remote) {
            $o->writeln("<info>Server is up to date.</info>");

            return;
        }

        $files    = Git::getAllFilesSince($rev_remote);
        $modified = $deleted = array();

        foreach ($files as $file) {
            if (strpos($file, ".") === 0) continue;

            if (is_file($file)) {
                $modified[] = $file;
            } else {
                $deleted[] = $file;
            }
        }

        $count_deleted  = count($deleted);
        $count_modified = count($modified);

        if ($o->isVerbose()) $o->writeln("<comment>- {$count_deleted} files will be deleted from server.</comment>");
        if ($o->isVerbose()) $o->writeln("<comment>- {$count_modified} files will be uploaded to server.</comment>");

        $dir_local = Git::getWorkingDirectory();
        $dir_local = str_replace(["\\", "/"], "/", $dir_local);

        if ($remove && !empty($deleted)) {
            $o->writeln("<info>Removing deleted files.</info>");
            if (!$dry) $ftp->log("[REV REMOVE][START] {$rev_remote} > {$rev_local}");

            foreach ($deleted as $index => $path_local) {
                $total        = $count_deleted;
                $index        = $index + 1;
                $path_local   = str_replace(["\\", "/"], "/", $path_local);
                $path_partial = str_replace($dir_local, "", $path_local);
                $path_partial = trim($path_partial, "/");

                if ($o->isVerbose()) $o->writeln("- {$path_partial} [{$index}/{$total}]");
                if (!$dry) $ftp->delete($path_partial);
            }

            if (!$dry) $ftp->log("[REV REMOVE][END] {$rev_remote} > {$rev_local}");
        }

        if (!empty($modified)) {
            $o->writeln("<info>Uploading modified files.</info>");

            if (!$dry) $ftp->log("[REV UPLOAD][START] {$rev_remote} > {$rev_local}");

            foreach ($modified as $index => $path_local) {
                $total        = $count_modified;
                $index        = $index + 1;
                $path_local   = str_replace(["\\", "/"], "/", $path_local);
                $path_partial = str_replace($dir_local, "", $path_local);
                $path_partial = trim($path_partial, "/");

                if ($o->isVerbose()) $o->writeln("- {$path_partial} [{$index}/{$total}]");
                if (!$dry) $ftp->upload($path_local, $path_partial, $backup);
            }
        }

        if (!$dry) {
            $ftp->log("[REV UPLOAD][STOP] {$rev_remote} > {$rev_local}");
            $ftp->setRevision($rev_local);
            $o->writeln("<info>Remote revision updated.</info>");
        }
    }


}