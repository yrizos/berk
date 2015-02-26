<?php
namespace Berk;

class Ftp
{
    CONST REVISION_FILENAME = '.berk-revision';
    CONST LOG_FILENAME = '.berk-log';

    private $conn;
    private $path;

    public function __construct($host, $port, $username, $password, $path)
    {
        $this->setRootPath($path);

        $conn = ftp_connect($host, $port, 30);

        if (!ftp_login($conn, $username, $password)) {
            ftp_close($conn);

            throw new \ErrorException("Couldn't connect to {$host}.");
        }

        if (!ftp_chdir($conn, $this->getRootPath())) {
            ftp_close($conn);

            throw new \ErrorException("Couldn't change directory to " . $this->getRootPath());
        }

        $this->conn = $conn;
    }

    public function __destruct()
    {
        ftp_close($this->conn);
    }

    private function setRootPath($path)
    {
        $this->path = rtrim($path, '/') . '/';
    }

    private function getRootPath()
    {
        return $this->path;
    }

    private function getConnection()
    {
        return $this->conn;
    }

    public function getRevision()
    {
        $size = ftp_size($this->getConnection(), self::REVISION_FILENAME);

        if ($size < 1) $this->setRevision('0');

        $temp = tempnam(sys_get_temp_dir(), 'berk');

        if (!ftp_get($this->getConnection(), $temp, self::REVISION_FILENAME, FTP_BINARY)) throw new \Exception("Couldn't retrieve revision information.");

        $revision = file_get_contents($temp);
        $revision = trim($revision);
        if (empty($revision)) $revision = 0;

        unlink($temp);

        return $revision;
    }

    public function setRevision($revision)
    {
        $revision = trim($revision);
        if (empty($revision)) $revision = 0;

        $temp = tempnam(sys_get_temp_dir(), 'berk');

        file_put_contents($temp, $revision);

        if (!ftp_put($this->getConnection(), self::REVISION_FILENAME, $temp, FTP_BINARY)) throw new \Exception("Couldn't update revision information.");

        unlink($temp);

        $this->log("Revision changed: " . $revision);

        return $revision;
    }

    public function log($message)
    {
        $log   = $this->getLog();
        $log[] = "[" . date('Y-m-d H:i:s') . "] " . trim($message);
        $log   = implode("\n", $log);
        $temp  = tempnam(sys_get_temp_dir(), 'berk');

        file_put_contents($temp, $log);

        if (!ftp_put($this->getConnection(), self::LOG_FILENAME, $temp, FTP_BINARY)) throw new \Exception("Couldn't update log.");

        unlink($temp);

        return true;
    }

    public function getLog()
    {
        if (ftp_size($this->getConnection(), self::LOG_FILENAME) < 1) return [];

        $temp = tempnam(sys_get_temp_dir(), 'berk');
        if (!ftp_get($this->getConnection(), $temp, self::LOG_FILENAME, FTP_BINARY)) throw new \Exception("Couldn't retrieve log information.");

        $log = file_get_contents($temp);
        $log = trim($log);
        $log = explode("\n", $log);
        $log = array_filter($log, function ($value) {
            return !empty($value);
        });

        unlink($temp);

        return $log;
    }
}