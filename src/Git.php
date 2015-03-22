<?php
namespace Berk;

class Git
{
    private static $working_directory = null;

    public static function isGitDirectory($directory)
    {
        if (!is_dir($directory)) throw new \InvalidArgumentException('Directory ' . $directory . ' is invalid.');
        $directory = realpath($directory) . DIRECTORY_SEPARATOR . '.git';

        return is_dir($directory);
    }

    public static function exec($arguments = '', $first_only = false)
    {

        $arguments = explode(' ', $arguments);
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

        if ($first_only) $output = is_array($output) && isset($output[0]) ? $output[0] : '';

        return $output;
    }

    public static function getVersion()
    {
        $output = self::exec('--version', true);
        $output = str_replace('git version', '', $output);

        return trim($output);
    }

    public static function getCurrentBranch()
    {
        $output = self::exec('symbolic-ref --short HEAD', true);

        return $output;
    }

    public static function getCurrentRevision()
    {
        $output = self::exec('rev-list --all --max-count=1', true);

        return $output;
    }

    public static function getPreviousRevision()
    {
        $output = self::exec('rev-list --all --max-count=2');
        $output = array_pop($output);

        return $output;
    }

    public static function getRevisions()
    {
        $output = self::exec('rev-list --all');

        return $output;
    }

    public static function getWorkingDirectory()
    {
        if (null == self::$working_directory) self::$working_directory = realpath(self::exec('rev-parse --show-toplevel', true));

        return self::$working_directory;
    }

    public static function inWorkingDirectory()
    {
        $working = self::getWorkingDirectory();

        return getcwd() === $working;
    }

    public static function getWorkingPath($path)
    {
        $wdir = self::getWorkingDirectory();
        $path = str_replace(["\\", "/"], DIRECTORY_SEPARATOR, $path);

        if (strpos($path, $wdir) !== 0) $path = $wdir . DIRECTORY_SEPARATOR . $path;

        return $path;
    }

    public static function getRevisionFiles($revision)
    {
        $output = self::exec('diff-tree --no-commit-id --name-only -r ' . $revision);
        $output = array_map(function ($path) {
            return Git::getWorkingPath($path);
        }, $output);

        return $output;
    }

    public static function getRevisionsBetween($revision_from = null, $revision_to = null)
    {
        $revisions     = self::getRevisions();
        $revisions     = array_reverse($revisions);
        $position_from = $revision_from ? array_search($revision_from, $revisions) : 0;
        $position_to   = $revision_to ? array_search($revision_to, $revisions) + 1 : count($revisions);

        if (
            $position_from === false
            || $position_to === false
            || $position_from > $position_to
        ) return [];

        $revisions = array_slice($revisions, $position_from, $position_to - $position_from);
        $revisions = array_reverse($revisions);

        return $revisions;
    }

    public static function getRevisionsFilesBetween($revision_from = null, $revision_to = null)
    {
        $revisions = self::getRevisionsBetween($revision_from, $revision_to);
        $files     = [];

        foreach($revisions as $revision) $files = array_merge($files, self::getRevisionFiles($revision));

        $files = array_unique($files);
        sort($files);

        return $files;
    }

    public static function getUncommittedFiles()
    {
        $output = self::exec('status --porcelain');
        if (empty($output)) $output = [];
        $output = array_map(function ($path) {
            $path = trim($path);
            $path = explode(' ', $path);
            $path = array_pop($path);

            return Git::getWorkingPath($path);
        }, $output);

        return $output;
    }
}
