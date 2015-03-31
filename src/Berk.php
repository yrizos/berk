<?php

namespace Berk;

class Berk
{
    const NAME = 'berk';
    const VERSION = '0.0.2';
    const CONFIG_FILENAME = '.berk.json';
    const EXPORT_DIRECTORY = '.berk';

    /** @var array */
    private $config = [];

    /**
     * @param string $config Path to configuration file
     */
    public function __construct($config = null)
    {
        $directory       = @Git::getWorkingDirectory();
        $this->directory = $directory;
        if (null === $config) $config = $this->getDirectory() . DIRECTORY_SEPARATOR . self::CONFIG_FILENAME;

        $this->config = self::parseConfiguration($config);
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * @return array
     */
    public function getAllFiles()
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getDirectory(), \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

        $files = [];
        foreach ($iterator as $object) {
            if ($object->isFile()) $files[] = $object->getRealPath();
        }

        return $this->removeIgnoredFiles($files);
    }

    /**
     * @param string|null $revision_from
     * @param string|null $revision_to
     * @param bool        $uncommited
     *
     * @return array
     */
    public function getFilesBetween($revision_from = null, $revision_to = null, $uncommited = false)
    {
        $files = null !== $revision_from && $revision_from === $revision_to ? [] : Git::getRevisionsFilesBetween($revision_from, $revision_to);

        if (true === $uncommited) $files = array_merge($files, Git::getUncommittedFiles());

        return $this->removeIgnoredFiles($files);
    }

    private function removeIgnoredFiles(array $files = [])
    {
        $ignore = $this->getConfiguration()['ignore'];
        $files  = array_filter($files, function ($path) use ($ignore) {
            if (strpos($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR) !== false) return false;

            foreach ($ignore as $ex) {
                if ((is_dir($ex) && strpos($path, $ex) === 0) || ($ex === $path)
                ) return false;
            }

            return true;
        });

        return $files;
    }

    public function getFtpConnection($server)
    {
        $c = isset($this->getConfiguration()['servers'][$server]) ? $this->getConfiguration()['servers'][$server] : false;

        if (!$c) throw new \InvalidArgumentException('Missing configuration for server ' . $server);

        return new Ftp($c['host'], $c['port'], $c['username'], $c['password'], $c['path']);
    }

    public static function parseConfiguration($path)
    {
        if (!is_file($path) || !is_readable($path)) throw new \InvalidArgumentException('Path ' . $path . ' is invalid.');

        $config = @json_decode(file_get_contents($path), true);

        if (!is_array($config)) throw new \Exception('Configuration file is invalid.');

        if (!isset($config['name']) || empty($config['name'])) {
            $name           = Git::getWorkingDirectory();
            $name           = explode(DIRECTORY_SEPARATOR, $name);
            $name           = array_pop($name);
            $config['name'] = $name;
        }

        if (!isset($config['servers']) || !is_array($config['servers'])) $config['servers'] = [];

        foreach ($config['servers'] as $k => $server) {
            if (!isset($server['host']) || !isset($server['username']) || !isset($server['password'])
            ) {
                throw new \Exception('Configuration for server ' . $k . ' is invalid.');
            }

            if (!isset($server['port'])) $server['port'] = 21;
            if (!isset($server['path'])) $server['path'] = '/';

            $config['servers'][$k] = $server;
        }

        $config['ignore']   = isset($config['ignore']) && is_array($config['ignore']) ? $config['ignore'] : [];
        $config['ignore'][] = $path;
        $config['ignore'][] = self::EXPORT_DIRECTORY;;
        $config['ignore'][] = '.git';
        $config['ignore'][] = '.idea';
        $config['ignore'][] = '.gitignore';
        $config['ignore'][] = '.gitattributes';
        $config['ignore'][] = '.travis.yml';
        $config['ignore'][] = 'LICENSE';
        $config['ignore'][] = 'README.md';

        foreach ($config['ignore'] as $key => $path) {
            if (!file_exists($path)) $path = Git::getWorkingPath($path);

            $path = realpath($path);
            if ($path) {
                $config['ignore'][$key] = $path;
            } else {
                unset($config['ignore'][$key]);
            }
        }

        $config['ignore'] = array_unique($config['ignore']);

        return $config;
    }


}