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
        $this->addOption("dry", "d", InputOption::VALUE_NONE, "dry run");
        $this->addOption("backup", "b", InputOption::VALUE_NONE, "backup remote files");
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        $time   = time();
        $config = $this->getApplication()->getConfiguration();
        $server = $i->getArgument("server");
        $dry    = $i->getOption("dry");
        $backup = $i->getOption("backup");

        if (!isset($config[$server])) {
            $this->writeError("Configuration for server {$server} not found.");

            return;
        }

        $config      = $config[$server];
        $ftp         = new Ftp($config["host"], $config["port"], $config["username"], $config["password"], $config["path"]);
        $rev_current = $ftp->getRevision();
        $rev_target  = Git::getLastCommit();
        $files       = Git::getAllFilesSince($rev_current);

        if (empty($files)) {
            $o->writeln("Server is up to date.");

            return;
        }

        $modified = $deleted = array();

        foreach ($files as $file) {
            if(strpos($file, '.') === 0) continue;

            if (is_file($file)) {
                $modified[] = $file;
            } else {
                $deleted[] = $file;
            }
        }

        $o->writeln(count($modified) . " files have been modified since revision {$rev_current}.");
        $o->writeln(count($deleted) . " files have been deleted since revision {$rev_current}.");
        $o->writeln("");
        $o->writeln("Uploading modified files.");

        $dir_local = Git::getWorkingDirectory();
        $dir_local = str_replace(["\\", "/"], "/", $dir_local);

        if (!$dry) $ftp->log("[REV UPLOAD][START] {$rev_current} > {$rev_target}");

        foreach ($modified as $path_local) {
            $path_local   = str_replace(["\\", "/"], "/", $path_local);
            $path_partial = str_replace($dir_local, "", $path_local);
            $path_partial = trim($path_partial, "/");

            $o->writeln("- " . $path_partial);

            if ($dry) continue;

            $ftp->upload($path_local, $path_partial, $backup);
        }

        if (!$dry) $ftp->log("[REV UPLOAD][STOP] {$rev_current} > {$rev_target}");
        if (!$dry) $ftp->setRevision($rev_target);

        $time = time() - $time;

        $o->writeln("");
        $o->writeln("Done in {$time} seconds. Bye!");
    }


}