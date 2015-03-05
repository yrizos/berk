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
        $this->addOption("commit", "c", InputOption::VALUE_REQUIRED, "Hash to use as remote reference point.", false);
        $this->addOption("dry", "d", InputOption::VALUE_NONE, "Dry run. Does not modify any files.");
        $this->addOption("remove", "r", InputOption::VALUE_NONE, "Remove remote files that don't exist locally.");
        $this->addOption("backup", "b", InputOption::VALUE_NONE, "Backup remote files before overwriting them.");
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $servers = $this->getApplication()->getConfiguration()['servers'];
        $server  = $i->getArgument("server");
        $dry     = $i->getOption("dry");
        $backup  = $i->getOption("backup");
        $remove  = $i->getOption("remove");
        $commit  = $i->getOption("commit");

        if (!$dry && !Git::isUpdated()) {
            $helper   = $this->getHelper('question');
            $question = new ConfirmationQuestion('<question>There are uncommitted changes. Continue (y/N)?</question> ', false);

            if (!$helper->ask($i, $o, $question)) return;
        }

        if (!isset($servers[$server])) {
            $o->writeln("<error>Configuration for server '" . $server . "' not found.</error>");

            return;
        }

        $ftp = new Ftp($servers[$server]["host"], $servers[$server]["port"], $servers[$server]["username"], $servers[$server]["password"], $servers[$server]["path"]);

        $msg = "Deploying to server: " . $server . ".";
        if ($dry) $msg .= " (dry run)";

        $o->writeln("<info>" . $msg . "</info>");

        $rev_remote = $commit ? $commit : $ftp->getRevision();
        $rev_local  = Git::getCurrentRevision();

        if ($o->isVerbose()) $o->writeln("<comment>- Local revision: " . $rev_local . "</comment>");
        if ($o->isVerbose()) $o->writeln("<comment>- Remote revision: " . $rev_remote . "</comment>");

        list($files_modified, $files_deleted) = $this->getFiles($rev_remote);

        $count_deleted  = count($files_deleted);
        $count_modified = count($files_modified);

        if ($o->isVerbose()) $o->writeln("<comment>- {$count_deleted} files should be deleted from server.</comment>");
        if ($o->isVerbose()) $o->writeln("<comment>- {$count_modified} files should be uploaded to server.</comment>");

        if ($count_deleted === 0 && $count_modified === 0) {
            $o->writeln("<info>Server is up to date</info>");

            return;
        }

        if ($remove && !empty($deleted)) {
            $o->writeln("<info>Removing files from server.</info>");
            if (!$dry) $ftp->log("[REV REMOVE][START] {$rev_remote} > {$rev_local}");

            foreach ($files_deleted as $index => $path_local) {
                $path_partial = str_replace(Git::getWorkingDirectory(), '', $path_local);
                $path_partial =
            }

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

    private function getFiles($revision)
    {
        $exclude = $this->getApplication()->getConfiguration()['exclude'];
        $files   = Git::getAllFilesSince($revision);
        $files   = array_filter($files, function ($path) use ($exclude) {
            foreach ($exclude as $x) {
                if (strpos($path, $x) === 0) return false;
            }

            return true;
        });

        $modified = $deleted = [];
        foreach ($files as $path) {
            if (is_file($path)) {
                $modified[] = $path;
            } else {
                $deleted[] = $path;
            }
        }

        return [$modified, $deleted];
    }

}