<?php
namespace Berk;

class Git
{
    private static function exec(array $arguments = [])
    {
        $arguments = array_map(function ($value) {
            $value = is_string($value) ? trim($value) : '';
            $value = escapeshellarg($value);

            return $value;
        }, $arguments);

        $arguments = array_filter($arguments, function ($value) {
            return !empty($value);
        });

        $arguments = implode(' ', $arguments);
        $command   = 'git ' . $arguments;

        exec($command, $output, $return);

        if ($return !== 0) {
            throw new \RuntimeException('Command ' . $command . ' returned exit code: ' . $return);
        }

        return $output;
    }

    public static function getVersion()
    {
        $output = self::exec(['--version']);
        $output = str_replace('git version', '', $output[0]);

        return trim($output);
    }

    public static function getCurrentBranch()
    {
        $output = self::exec(['symbolic-ref', '--short', 'HEAD']);

        return $output[0];
    }

    public static function isUpdated()
    {
        $output = self::exec(['status', '-s']);
        $output = is_array($output) && !empty($output) ? $output[0] : false;

        return empty($output[0]);
    }

    public static function getWorkingDirectory()
    {
        $output = self::exec(['rev-parse', '--show-toplevel']);
        $output = $output[0];

        if (is_dir($output)) $output = realpath($output);

        return $output;
    }

    public static function inWorkingDirectory()
    {
        return self::getWorkingDirectory() === getcwd();
    }

    public static function getLastCommit()
    {
        $output = self::exec(['rev-list', '--all', '--max-count=1']);

        return $output[0];
    }

    public static function getCommitsSince($hash)
    {
        $hash   = trim($hash) . '^..HEAD';
        $output = self::exec(['rev-list', $hash]);

        return $output;
    }

    public static function getCommitFiles($hash)
    {
        $dir    = self::getWorkingDirectory();
        $output = self::exec(['diff-tree', '--no-commit-id', '--name-only', '-r', $hash]);
        $output = array_map(function ($value) use ($dir) {
            return $dir . "/" . $value;
        }, $output);

        return $output;
    }

    public static function getAllFilesSince($hash)
    {
        $files  = [];
        $hashes = Git::getAllCommitsSince($hash);

        foreach ($hashes as $hash) {
            $hash_files = Git::getCommitFiles($hash);

            foreach ($hash_files as $file) $files[] = $file;
        }

        $files = array_unique($files);
        ksort($files);

        return $files;
    }

    public static function getAllCommits()
    {
        $output = self::exec(['rev-list', '--all']);

        return $output;
    }

    public static function getAllCommitsSince($hash)
    {
        $all = self::getAllCommits();
        $key = array_search($hash, $all, true);

        if ($key === false) return $all;
        if ($key === 0) return [];

        return array_slice($all, 0, $key);
    }
}