<?php
namespace Berk;

class Git
{
    private static function exec($command, array $arguments = [])
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

        if (!empty($arguments)) $command .= ' ' . $arguments;

        exec($command, $output, $return);

        if ($return !== 0) {
            throw new \RuntimeException('Command ' . $command . ' returned exit code: ' . $return);
        }

        return $output;
    }

    public static function getVersion()
    {
        $output = self::exec('git', ['--version']);
        $output = str_replace('git version', '', $output[0]);

        return trim($output);
    }

    public static function getCurrentBranch()
    {
        $output = self::exec('git', ['symbolic-ref', '--short', 'HEAD']);

        return $output[0];
    }

    public static function isUpdated()
    {
        $output = self::exec('git', ['status', '-s']);
        $output = is_array($output) && !empty($output) ? $output[0] : false;

        return empty($output[0]);
    }

    public static function getWorkingDirectory()
    {
        $output = self::exec('git', ['rev-parse', '--show-toplevel']);
        $output = $output[0];

        if (is_dir($output)) $output = realpath($output);

        return $output;
    }

    public static function inWorkingDirectory()
    {
        return self::getWorkingDirectory() === getcwd();
    }

    public static function getCommitsSince($hash)
    {
        $hash   = trim($hash) . '^..HEAD';
        $output = self::exec('git', ['rev-list', $hash]);

        return $output;
    }

    public static function getCommitFiles($hash)
    {
        $output = self::exec('git', ['diff-tree', '--no-commit-id', '--name-only', '-r', $hash]);

        return $output;
    }

    public static function getFilesSince($hash)
    {
        $files  = [];
        $hashes = Git::getCommitsSince($hash);

        foreach ($hashes as $hash) {
            $hash_files = Git::getCommitFiles($hash);

            foreach ($hash_files as $file) $files[] = $file;
        }

        $files = array_unique($files);
        ksort($files);

        return $files;

    }

}