<?php
namespace Berk\Command;

use Berk\Command;
use Berk\Git;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;


class PackSinceCommitCommand extends Command
{
    protected function configure()
    {
        $this->setName('pack:since');

        $this->addArgument('hash', InputArgument::OPTIONAL, 'commit hash');
        $this->addArgument('dir', InputArgument::OPTIONAL, 'pack dir');
    }

    public function execute(InputInterface $i, OutputInterface $o)
    {
        if (!$this->askConfirmation($i, $o)) return;

        $wdir   = Git::getWorkingDirectory();
        $helper = $this->getHelper('question');
        $hash   = $i->getArgument('hash');
        $dir    = $i->getArgument('dir');

        if (!$hash) {
            $question = new Question('<fg=yellow>Hash?</fg=yellow> ');
            $hash     = $helper->ask($i, $o, $question);
        }

        if (!$dir) {
            $dir      = getcwd() . "/.berk";
            $question = new Question("<fg=yellow>Dir?</fg=yellow> ({$dir}) ", $dir);
            $dir      = $helper->ask($i, $o, $question);
        }

        $files = Git::getFilesSince($hash);

        if (empty($files)) {
            $o->writeln("<fg=yellow>Couldn't find any files to pack</fg=yellow>");

            return false;
        }

        $tmp     = explode(DIRECTORY_SEPARATOR, $wdir);
        $project = array_pop($tmp);
        $dir     = str_replace(["\\", "/"], "/", $dir) . "/{$project}-{$hash}";
        $dir     = rtrim($dir, '/');

        @mkdir($dir, 0777, true);

        if (!is_dir($dir)) {
            $o->writeln("<fg=red>Couldn't create directory {$dir}</fg=red>");

            return false;
        }

        $o->writeln("");
        $o->writeln("Copying " . count($files) . " files to " . $dir);

        foreach ($files as $file) {
            $path_old = $wdir . '/' . $file;

            if(!is_file($path_old)) {
                $o->writeln("- <fg=red>Failed " . $file . "</fg=red>");
                continue;
            }

            $path_new = $dir . '/' . $file;
            $dir_new  = dirname($path_new);

            if (!is_dir($dir_new)) mkdir($dir_new, 0777, true);

            copy($path_old, $path_new);

            $o->writeln("- Copied " . $file);
        }
    }

}