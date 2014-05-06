<?php

namespace Spotlab\Safeguard;

use Clouddueling\Mysqldump\Mysqldump;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class Guardian
{
    protected $config;

    public function __construct($config_path)
    {
        if (file_exists($config_path)) {
            $this->config = Yaml::parse($config_path);
        } else {
            throw new \Exception('Config file does not exists', 0);
        }
    }

    /**
     * @return array $return
     */
    public function getProjects()
    {
        // Return array
        $return = array_keys($this->config);

        // Exception if not defined
        if (empty($return)) {
            throw new \Exception('No "project" to backup find on config file', 0);
        }

        return $return;
    }

    /**
     * @param  string $project
     * @return array  $return
     */
    public function backupArchive($project)
    {
        // Exception if not defined
        if (!isset($this->config[$project]['archive'])) {
            throw new \Exception('No "Archive config" for this project', 1);
        }

        // Get backup settings
        $settings = $this->getArchiveSettings($project);

        // Get backup file prefix
        $backupFilePrefix = '';
        if (!empty($settings['backup_file_prefix'])) {
            $backupFilePrefix = $settings['backup_file_prefix'];
        }

        // Set filename
        $filename = $backupFilePrefix . date('Ymd_His') . '.tar';

        // Data required to backup Database
        $path = $this->getBackupPath($project, 'archive');
        
        // Create Archive
        $phar = new \PharData($path . '/' . $filename);
        $phar->buildFromIterator(new \ArrayIterator($this->getArchiveFilesList($project)));

        // Compress and unlink none compress archive
        $phar->compress(\Phar::GZ);
        unlink($path . '/' . $filename);

        return $this->getBackupInfo($path, $filename);
    }

    /**
     * @param  string $project
     * @return array  $return
     */
    public function getArchiveFilesList($project)
    {
        $return = array();

        // Folders to backup
        $settings = $this->getArchiveSettings($project);
        $commonPath = $this->getCommonPath($settings['folders']);

        // Search file with compression
        $finder = new Finder();
        $finder = $finder->files()->followLinks();

        foreach ($settings['folders'] as $folder) {
            $finder = $finder->in($folder);

            //Settings
            if (!empty($settings['minsize'])) {
                $finder = $finder->size($settings['minsize']);
            }

            if (!empty($settings['maxsize'])) {
                $finder = $finder->size($settings['maxsize']);
            }

            if (!empty($settings['exclude_folders'])) {
                foreach ($settings['exclude_folders'] as $exclude_folder) {
                    $exclude_folder = str_replace(realpath($folder) . '/', '', realpath($exclude_folder));
                    $finder = $finder->exclude($exclude_folder);
                }
            }

            if (!empty($settings['exclude_files'])) {
                foreach ($settings['exclude_files'] as $exclude_file) {
                    if (substr($exclude_file, 0, 1) == '.') {
                        $exclude_file = '*' . $exclude_file;
                    }
                    $finder = $finder->notName($exclude_file);
                }
            }

            # Create list to archive
            foreach ($finder as $file) {
                $return[str_replace($commonPath . '/', '', $file->getRealpath())] = $file->getRealpath();
            }
        }

        // Duplicates are removed if they exist
        $return = array_unique($return);

        if (empty($return)) {
            throw new \Exception('No such files to archive for this project', 1);
        }

        return $return;
    }

    /**
     * @param  string $project
     * @return array  $return
     */
    public function backupDatabase($project)
    {
        // Exception if not defined
        if (!isset($this->config[$project]['database'])) {
            throw new \Exception('No "Database config" for this project', 1);
        }

        // Data required to backup Database
        $path = $this->getBackupPath($project, 'database');

        // Get database settings
        $settings = $this->getDatabaseSettings($project);

        // Get backup file prefix
        $backupDbPrefix = '';
        if (!empty($settings['backup_file_prefix'])) {
            $backupDbPrefix = $settings['backup_file_prefix'];
        }

        // Set filename
        $filename = $backupDbPrefix . date('Ymd_His') . '.sql';
        
        // Get dtatbase access
        $access = $this->getDatabaseAccess($project);
        
        // Dump action
        $dump = new Mysqldump($access['name'], $access['user'], $access['password'], $access['host'], $access['driver'], $settings);
        $dump->start($path . '/' . $filename);

        return $this->getBackupInfo($path, $filename);
    }

    /**
     * @param  string $project
     * @return array  $return
     */
    private function getDatabaseAccess($project)
    {
        // Exception if not defined
        if (empty($this->config[$project]['database'])) {
            throw new \Exception('Config for "Database Backup" is not defined', 1);
        } else {
            $config = $this->config[$project]['database'];
        }

        // Return array
        $return = array();

        // Default values
        $default = array(
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'password' => '',
            'name' => '',
            'user' => '',
        );

        foreach (array_keys($default) as $key) {
            if (array_key_exists($key, $config) && (!empty($config[$key]) || $config[$key] === false )) {
                $return[$key] = $config[$key];
            } elseif ($key == 'user' || $key == 'name') {
                throw new \Exception('Database "' . $key . '" must be defined', 0);
            } else {
                $return[$key] = $default[$key];
            }
        }

        return $return;
    }

    /**
     * @param  string $project
     * @return array  $return
     */
    private function getDatabaseSettings($project)
    {
        // Exception if not defined
        if (empty($this->config[$project]['database'])) {
            throw new \Exception('Config for "Database Backup" is not defined', 0);
        } else {
            $config = $this->config[$project]['database'];
        }

        // Return array
        $return = array();

        // Default values
        $default = array(
            'include_tables' => array(),
            'exclude_tables' => array(),
            'compress' => 'None',
            'no_data' => false,
            'add_drop_database' => false,
            'add_drop_table' => false,
            'single_transaction' => false,
            'lock_tables' => false,
            'add_locks' => false,
            'extended_insert' => false,
            'disable_foreign_keys_check' => false,
            'backup_file_prefix' => false
        );

        foreach (array_keys($default) as $key) {
            if (array_key_exists($key, $config) && (!empty($config[$key]) || $config[$key] === false )) {
                $return[str_replace('_', '-', $key)] = $config[$key];
            } else {
                $return[str_replace('_', '-', $key)] = $default[$key];
            }
        }

        return $return;
    }

    /**
     * @param  string $project
     * @return array  $return
     */
    private function getArchiveSettings($project)
    {
        // Exception if not defined
        if (empty($this->config[$project]['archive'])) {
            throw new \Exception('Config for "Archive Backup" is not defined', 0);
        } else {
            $config = $this->config[$project]['archive'];
        }

        // Return array
        $return = array();

        // Default values
        $default = array(
            'minsize' => false,
            'maxsize' => false,
            'exclude_folders' => false,
            'exclude_files' => false,
            'folders' => false,
            'backup_file_prefix' => false
        );

        foreach (array_keys($default) as $key) {
            if (array_key_exists($key, $config) && (!empty($config[$key]) || $config[$key] === false )) {
                $return[$key] = $config[$key];
            }
        }

        // Default values
        if (empty($return['folders'])) {
            throw new \Exception('Archive "folders" must be defined', 0);
        }

        return $return;
    }

    /**
     * @param  string $project
     * @param  string $type
     * @return string $return
     */
    private function getBackupPath($project, $type)
    {
        // Exception if not defined
        if (empty($this->config[$project][$type]['backup_path'])) {
            throw new \Exception('Backup path for "' . ucfirst($type) . ' Backup" is not defined', 0);
        } else {
            $path = $this->config[$project][$type]['backup_path'];
        }

        // Return string
        $return = '';

        // Create folders if not exists
        if (!file_exists($path)) {
            $fs = new Filesystem();
            $fs->mkdir($path, 0700);
        }

        $return = realpath($path);

        return $return;
    }

    /**
     * @param  string $path
     * @param  string $filename
     * @return array  $return
     */
    private function getBackupInfo($path, $filename)
    {
        // Return array
        $return = array();

        // Search file with compression
        $finder = new Finder();
        $iterator = $finder->in($path)->name($filename . '*')->followLinks();
        foreach ($iterator as $file) {
            $filename = $file->getFilename();
        }

        // Realpath of backup
        $return['name'] = $path . '/' . $filename;

        // Size of backup
        $return['size'] = round((filesize($return['name']) / 1000000), 2) . 'Mo';

        return $return;
    }

    /**
     * @param  array  $folders
     * @return string $path
     */
    private function getCommonPath($folders)
    {
        $return = array();
        foreach ($folders as $i => $folder) {
            $return[$i] = explode('/', realpath($folder));
            unset($return[$i][0]);

            $arr[$i] = count($return[$i]);
        }

        $min = min($arr);
        for ($i = 0; $i < count($return); $i++) {
            while (count($return[$i]) > $min) {
                array_pop($return[$i]);
            }

            $return[$i] = '/' . implode('/' , $return[$i]);
        }

        // Check every parents and reduce array
        $return = array_unique($return);
        while (count($return) !== 1) {
            $return = array_map('dirname', $return);
            $return = array_unique($return);
        }

        reset($return);

        return current($return);
    }
}
