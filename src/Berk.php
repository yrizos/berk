<?php

namespace Berk;

use Berk\Command\ConfigCommand;
use Berk\Command\DeployCommand;
use Berk\Command\CheckCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

class Berk extends SymfonyApplication
{
    const NAME = 'berk';
    const VERSION = '0.0.1';
    const FILE_CONFIGURATION = 'berk.json';

    private $configuration = [];

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->add(new ConfigCommand());
        $this->add(new CheckCommand());
    }

    public function getConfigurationPath()
    {
        return Git::getWorkingDirectory() . '/' . self::FILE_CONFIGURATION;
    }

    public function getConfiguration()
    {
        if (!empty($this->configuration)) return $this->configuration;

        $config = [];
        $path   = $this->getConfigurationPath();
        if (is_file($path)) {
            $config = file_get_contents($path);
            $config = json_decode($config, true);
        }

        return $this->configuration = $config;
    }

    public function getGitInfo()
    {
        return [
            'version'     => Git::getVersion(),
            'branch'      => Git::getCurrentBranch(),
            'w. dir'      => Git::getWorkingDirectory(),
            'last commit' => Git::getLastCommit(),
        ];
    }

}