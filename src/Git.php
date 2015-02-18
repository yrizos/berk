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

        if (!is_array($output)) $output = [''];

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

        return empty($output[0]);
    }

    public static function getWorkingDirectory()
    {
        $output = self::exec('git', ['rev-parse', '--show-toplevel']);
        $output = $output[0];

        if(is_dir($output)) $output = realpath($output);

        return $output;
    }

}