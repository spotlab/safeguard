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

            // Create list to archive
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
        $dumpSettings = $this->getDatabaseDumpSettings($project);

        // Get backup file prefix
        $backupDbPrefix = '';
        if (!empty($settings['backup_file_prefix'])) {
            $backupDbPrefix = $settings['backup_file_prefix'];
        }

        // Set filename
        $filename = $backupDbPrefix . date('Ymd_His') . '.sql';

        // Dump action
        $dump = new Mysqldump($settings['name'], $settings['user'], $settings['password'], $settings['host'], $settings['driver'], $dumpSettings);
        $dump->start($path . '/' . $filename);

        return $this->getBackupInfo($path, $filename);
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
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'password' => '',
            'name' => '',
            'user' => '',
            'keep_backups' => 10,
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

        // Return array
        $return = array_merge($default, $config);

        if (empty($return['user'])) {
            throw new \Exception('Database "user" must be defined', 0);
        }

        if (empty($return['name'])) {
            throw new \Exception('Database "name" must be defined', 0);
        }

        return $return;
    }

        /**
     * @param  string $project
     * @return array  $return
     */
    private function getDatabaseDumpSettings($project)
    {
        $settings = $this->getDatabaseSettings($project);

        // Return array
        $return = array(
            'include-tables' => $settings['include_tables'],
            'exclude-tables' => $settings['exclude_tables'],
            'compress' => $settings['compress'],
            'no-data' => $settings['no_data'],
            'add-drop-database' => $settings['add_drop_database'],
            'add-drop-table' => $settings['add_drop_table'],
            'single-transaction' => $settings['single_transaction'],
            'lock-tables' => $settings['lock_tables'],
            'add-locks' => $settings['add_locks'],
            'extended-insert' => $settings['extended_insert'],
            'disable-foreign-keys-check' => $settings['disable_foreign_keys_check']
        );

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

        // Default values
        $default = array(
            'keep_backups' => 10,
            'minsize' => false,
            'maxsize' => false,
            'exclude_folders' => false,
            'exclude_files' => false,
            'folders' => false,
            'backup_file_prefix' => false
        );

        // Return array
        $return = array_merge($default, $config);

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

    /**
     * @param  string $project
     * @return array  $return
     */
    public function cleanBackups($project, $type)
    {
        // Exception if not defined
        if (!isset($this->config[$project][$type])) {
            throw new \Exception('No "' . ucfirst($type) . ' config" for this project', 1);
        }

        // Data required to backup Database
        $path = $this->getBackupPath($project, $type);

        // Search file with compression
        $finder = new Finder();
        $finder = $finder->files()->followLinks()->sortByName()->in($path);

        // Get settings
        if ($type == 'archive') {
            $settings = $this->getArchiveSettings($project);
            $finder = $finder->name('*.tar.gz');
        } else {
            $settings = $this->getDatabaseSettings($project);
            $finder = $finder->notName('*.tar.gz');
        }

        // Create list to archive
        foreach ($finder as $file) {
            $allfiles[] = $file->getRealpath();
        }

        // Removing a number of backups
        $keep_backups = $settings['keep_backups'];
        if ($keep_backups != 0) {
            $removeFiles = array_slice($allfiles, 0, $keep_backups * -1);
        }

        // Removing
        $fs = new Filesystem();
        $fs->remove($removeFiles);

        return count($removeFiles) . ' ' . $type .' backups removed';
    }
}
