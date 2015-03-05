<?php
namespace Berk;

class Git
{
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

    public static function getNormalizedPath($path)
    {
        $path = str_replace(["\\", "/"], "/", $path);
        $path = rtrim($path, "/");

        return $path;
    }

    public static function getGitVersion()
    {
        $output = self::exec('--version', true);
        $output = str_replace('git version', '', $output);

        return $output;
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

    public static function getWorkingDirectory()
    {
        $output = self::exec('rev-parse --show-toplevel', true);

        if (!empty($output) && is_dir($output)) {
            $output = realpath($output);
            $output = self::getNormalizedPath($output);

            return $output;
        }

        return false;
    }

    public static function inWorkingDirectory()
    {
        $current = self::getNormalizedPath(getcwd());
        $working = self::getWorkingDirectory();

        return $current === $working;
    }

    public static function getWorkingPath($path)
    {
        $dir  = self::getWorkingDirectory() . "/";
        $path = self::getNormalizedPath($path);

        if (strpos($path, $dir) !== 0) $path = $dir . $path;

        return $path;
    }

    public static function getRevisions()
    {
        $output = self::exec('rev-list --all');
        $output = array_reverse($output);

        return $output;
    }

    public static function getRevisionsBetween($revision_from = null, $revision_to = null)
    {
        $revision_from = trim(strval($revision_from));
        $revision_to   = trim(strval($revision_to));
        $revisions     = self::getRevisions();

        if (empty($revisions)) return [];

        $position_from = array_search($revision_from, $revisions);
        $position_to   = array_search($revision_to, $revisions);

        if ($position_from === false) $position_from = 0;
        if ($position_to === false) $position_to = count($revisions) - 1;

        $length = $position_to - $position_from + 1;
        if ($length < 1) return [];

        return array_slice($revisions, $position_from, $length);
    }

    public static function getRevisionFiles($revision)
    {
        $output = self::exec('diff-tree --no-commit-id --name-only -r ' . $revision);
        $output = array_map(function ($path) {
            return Git::getWorkingPath($path);
        }, $output);

        return $output;
    }

    public static function getRevisionFilesBetween($revision_from = null, $revision_to = null)
    {
        $revisions = self::getRevisionsBetween($revision_from, $revision_to);
        $files     = [];

        foreach ($revisions as $revision) $files = $files + self::getRevisionFiles($revision);

        $files = array_unique($files);
        sort($files);

        return $files;
    }


    public static function isUpdated()
    {
        $output = self::exec('status -s');
        $output = is_array($output) && !empty($output) ? $output[0] : false;

        return empty($output[0]);
    }

    public static function getCommitsSince($revision)
    {
        $revision = trim($revision) . '^..HEAD';
        $output   = self::exec('rev-list ' . $revision);

        return $output;
    }

    public static function getCommitFiles($revision)
    {
        $output = self::exec('diff-tree --no-commit-id --name-only -r ' . $revision);
        $output = array_map(function ($path) {
            return Git::getWorkingPath($path);
        }, $output);

        return $output;
    }

    public static function getAllFilesSince($revision)
    {
        $files     = Git::getModifiedFiles();
        $revisions = Git::getAllCommitsSince($revision);

        foreach ($revisions as $revision) {
            $revision_files = Git::getCommitFiles($revision);

            foreach ($revision_files as $file) $files[] = $file;
        }

        $files = array_unique($files);
        ksort($files);

        return $files;
    }

    public static function getModifiedFiles()
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


    public static function getAllCommitsSince($revision)
    {
        $all = self::getAllCommits();
        $key = array_search($revision, $all, true);

        if ($key === false) return $all;
        if ($key === 0) return [];

        return array_slice($all, 0, $key);
    }
}