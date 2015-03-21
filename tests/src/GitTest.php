<?php

namespace BerkTest;

use Berk\Git;

class GitTest extends \PHPUnit_Framework_TestCase
{
    private $dir;

    public function setUp()
    {
        $this->dir = realpath(__DIR__ . '/../../');

        chdir($this->dir);
    }

    public function testWorkingDirectory()
    {

        $this->assertEquals($this->dir, Git::getWorkingDirectory());
        $this->assertTrue(Git::inWorkingDirectory());
    }

    public function testGetVersion()
    {
        $output = exec('git --version');
        $output = str_replace('git version', '', $output);
        $output = trim($output);

        $this->assertEquals($output, Git::getVersion());
    }

    public function testGetCurrentBranch()
    {
        $output = exec('git symbolic-ref --short HEAD');

        $this->assertEquals($output, Git::getCurrentBranch());
    }

    public function testGetRevisions()
    {
        exec('git rev-list --all', $output);

        $this->assertEquals($output[0], Git::getCurrentRevision());
        $this->assertEquals($output[1], Git::getPreviousRevision());
        $this->assertEquals($output, Git::getRevisions());
    }

    public function testGetRevisionFiles()
    {
        $revision = 'de98f583f25a3d8aee4be9169cc77c52cfab9aee';
        $output   = [
            'src/Application.php',
            'src/Command/DeployCommand.php',
            'src/Command/ExportCommand.php',
            'src/Command/InfoCommand.php',
            'src/Git.php',
        ];

        $output = array_map(function ($file) {
            return Git::getWorkingPath($file);
        }, $output);

        $this->assertEquals($output, Git::getRevisionFiles($revision));
    }

    public function testGetRevisionsBetween()
    {
        $this->assertEquals(Git::getRevisions(), Git::getRevisionsBetween());

        $revs = [
            'ce525406bf610237237193bb670aea95f001ea56',
            '04d11ab85386f884f5f9852cb8d12d0764aee4f8',
            '4f1ce916e61e222fd621a8a6dcce4057eb41730b',
            '4d8236b93a550cb3a52c5268c5d11d02e8567f3e',
            '26ffd297ca0a2c2a4a70fb163fd57c43b0361e15',
        ];

        $this->assertEquals($revs, Git::getRevisionsBetween('26ffd297ca0a2c2a4a70fb163fd57c43b0361e15', 'ce525406bf610237237193bb670aea95f001ea56'));
    }


}