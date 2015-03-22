<?php
namespace Berk;

class Ftp
{
    CONST REVISION_FILENAME = '.berk-revision';
    CONST LOG_FILENAME = '.berk-log';

    private $conn;
    private $path;
    private $host;
    private $port;
    private $username;
    private $password;

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

        $this->conn     = $conn;
        $this->host     = $host;
        $this->port     = $port;
        $this->username = $username;
        $this->password = $password;
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
        if (empty($revision)) $revision = '0';

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

        $this->log("[REV CHANGE] " . $revision);

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

        return array_slice($log, -1000);
    }

    public function fileExists($remote)
    {
        $remote = str_replace("\\", "/", $remote);
        $remote = ltrim($remote, "/");

        return ftp_size($this->getConnection(), $remote) > 0;
    }

    public function upload($local, $remote, $backup = false)
    {
        $backup = $backup === true;
        $remote = str_replace("\\", "/", $remote);
        $remote = ltrim($remote, "/");

        if ($backup && $this->fileExists($remote)) {
            $backup = $remote . "." . time() . ".berk";
            $rename = ftp_rename($this->getConnection(), $remote, $backup);

            if (!$rename) throw new \ErrorException("Backing up {$remote} failed.");
        }

        $this->mkdir($remote);
        $upload = ftp_put($this->getConnection(), $remote, $local, FTP_BINARY);

        if (!$upload) throw new \ErrorException("Uploading {$remote} failed.");

        return true;
    }

    public function delete($remote)
    {
        $remote = str_replace("\\", "/", $remote);
        $remote = ltrim($remote, "/");

        $result = false;
        if ($this->fileExists($remote)) {
            $result = @ftp_delete($this->getConnection(), $remote);
        }

        $dirname = dirname($remote);
        $list    = @ftp_nlist($this->getConnection(), $dirname);

        if (is_array($list) && empty($list)) ftp_rmdir($this->getConnection(), $dirname);

        return $result;
    }

    private function mkdir($remote)
    {
        $remote = str_replace("\\", "/", $remote);
        $remote = ltrim($remote, "/");
        $remote = explode('/', $remote);
        array_pop($remote);

        if (empty($remote)) return true;

        $current = ftp_pwd($this->getConnection());

        foreach ($remote as $dir) {
            if (!@ftp_chdir($this->getConnection(), $dir)) {
                ftp_mkdir($this->getConnection(), $dir);
                ftp_chdir($this->getConnection(), $dir);
            }
        }

        ftp_chdir($this->getConnection(), $current);

        return true;
    }
}