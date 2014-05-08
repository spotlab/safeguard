<?php

use Spotlab\Safeguard\Guardian;

class GuardianTest extends PHPUnit_Framework_TestCase
{
    protected $guardian;

    public function __construct()
    {
        $this->guardian = new Guardian(__DIR__ . '/../tests/examples/config.yml');
    }

    public function testgetProjects()
    {
        $projects = $this->guardian->getProjects();
        $this->assertEquals($projects, array('projetA', 'projetB', 'projetC', 'projetD'));
    }

    public function testgetDatabaseAccess()
    {
        $return = $this->callPrivateMethod($this->guardian, 'getDatabaseAccess', 'projetA');
        $this->assertEquals($return, array(
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'password' => 't2eV9hOVPKzXly3tKZau',
            'name' => 'projetA',
            'user' => 'projetA',
            'backup_file_prefix' => 'projetA_',
            )
        );

        $return = $this->callPrivateMethod($this->guardian, 'getDatabaseAccess', 'projetB');
        $this->assertEquals($return, array(
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'password' => 'zXly3tKZaut2eV9hOVPK',
            'name' => 'projetB',
            'user' => 'projetB',
            'backup_file_prefix' => false,
            )
        );

        try {
            $this->callPrivateMethod($this->guardian, 'getDatabaseAccess', 'projetC');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Config for "Database Backup" is not defined');
        }
    }

    public function testgetDatabaseSettings()
    {
        $return = $this->callPrivateMethod($this->guardian, 'getDatabaseSettings', 'projetA');
        $this->assertEquals($return, array(
            'include-tables' => array(),
            'exclude-tables' => array(),
            'compress' => 'GZIP',
            'no-data' => false,
            'add-drop-database' => false,
            'add-drop-table' => false,
            'single-transaction' => false,
            'lock-tables' => false,
            'add-locks' => false,
            'extended-insert' => false,
            'disable-foreign-keys-check' => false,
            'backup-file-prefix' => 'projetA_',
            )
        );

        $return = $this->callPrivateMethod($this->guardian, 'getDatabaseSettings', 'projetB');
        $this->assertEquals($return, array(
            'include-tables' => array(),
            'exclude-tables' => array(),
            'compress' => 'None',
            'no-data' => false,
            'add-drop-database' => false,
            'add-drop-table' => false,
            'single-transaction' => false,
            'lock-tables' => false,
            'add-locks' => false,
            'extended-insert' => false,
            'disable-foreign-keys-check' => false,
            'backup-file-prefix' => false
            )
        );

        try {
            $this->callPrivateMethod($this->guardian, 'getDatabaseSettings', 'projetC');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Config for "Database Backup" is not defined');
        }
    }

    public function testgetArchiveSettings()
    {
        try {
            $this->callPrivateMethod($this->guardian, 'getArchiveSettings', 'projetB');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Config for "Archive Backup" is not defined');
        }

        $return = $this->callPrivateMethod($this->guardian, 'getArchiveSettings', 'projetC');
        $this->assertEquals($return, array('folders' => array('/home/admin/www/projetC/current/web/assets')));

        try {
            $this->callPrivateMethod($this->guardian, 'getArchiveSettings', 'ProjectNotExist');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Config for "Archive Backup" is not defined');
        }

        $return = $this->callPrivateMethod($this->guardian, 'getArchiveSettings', 'projetD');
        $this->assertEquals($return, array(
            'minsize' => '>= 0',
            'maxsize' => '<= 2G',
            'exclude_folders' => array('tests/debug/iterator/folderB/exclude'),
            'exclude_files' => array('exclude.gif', '.jpg'),
            'folders' => array('tests/debug/iterator/folderA', 'tests/debug/iterator/folderB', 'tests/debug/iterator/folderC/subfolder')
        ));
    }

    public function testgetArchiveFilesList()
    {
        $return = $this->callPrivateMethod($this->guardian, 'getArchiveFilesList', 'projetD');
        $this->assertEquals($return, array(
            'folderA/chuckA.gif' => realpath('tests/debug/iterator/folderA/chuckA.gif'),
            'folderB/chuckB.gif' => realpath('tests/debug/iterator/folderB/chuckB.gif'),
            'folderB/chuckBbis.gif' => realpath('tests/debug/iterator/folderB/chuckBbis.gif'),
            'folderB/subfolder/chuckBter.gif' => realpath('tests/debug/iterator/folderB/subfolder/chuckBter.gif'),
            'folderC/subfolder/chuckC.gif' => realpath('tests/debug/iterator/folderC/subfolder/chuckC.gif'),
            'folderC/subfolder/subsubfolder/chuckCbis.gif' => realpath('tests/debug/iterator/folderC/subfolder/subsubfolder/chuckCbis.gif'),
        ));

        $this->assertFalse(array_key_exists('folderB/exclude/chuck.gif', $return));
        $this->assertFalse(array_key_exists('folderA/norris.jpg', $return));
        $this->assertFalse(array_key_exists('folderC/exclude.gif', $return));

        try {
            $this->callPrivateMethod($this->guardian, 'getArchiveSettings', 'ProjectNotExist');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Config for "Archive Backup" is not defined');
        }
    }

    public function testgetBackupPath()
    {
        $return = $this->callPrivateMethod($this->guardian, 'getBackupPath', 'projetA', 'database');
        $this->assertEquals($return, realpath('/tmp/backup/projetA'));

        $return = $this->callPrivateMethod($this->guardian, 'getBackupPath', 'projetA', 'archive');
        $this->assertEquals($return, realpath('/tmp/backup/projetA'));

        try {
            $this->callPrivateMethod($this->guardian, 'getBackupPath', 'projetA', 'KeyNotExist');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Backup path for "KeyNotExist Backup" is not defined');
        }

        $return = $this->callPrivateMethod($this->guardian, 'getBackupPath', 'projetB', 'database');
        $this->assertEquals($return, realpath('/tmp/backup/projetB'));

        try {
            $this->callPrivateMethod($this->guardian, 'getBackupPath', 'projetB', 'archive');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Backup path for "Archive Backup" is not defined');
        }

        try {
            $this->callPrivateMethod($this->guardian, 'getBackupPath', 'projetC', 'database');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Backup path for "Database Backup" is not defined');
        }

        try {
            $this->callPrivateMethod($this->guardian, 'getBackupPath', 'ProjectNotExist', 'database');
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), 'Backup path for "Database Backup" is not defined');
        }
    }

    public function testgetBackupInfo()
    {
        $projects = $this->callPrivateMethod($this->guardian, 'getBackupInfo', __DIR__ . '/examples', 'archive');
        $this->assertEquals($projects, array('name' => __DIR__ . '/examples/archive.tar.gz', 'size' => '2.05Mo'));
    }

    public function testgetCommonPath()
    {
        $path = $this->callPrivateMethod($this->guardian, 'getCommonPath', array('tests/debug/iterator/folderA', 'tests/debug/iterator/folderB', 'tests/debug/iterator/folderC/subfolder'));
        $this->assertEquals($path, realpath('tests/debug/iterator'));
    }

    /**
     * @param mixed $object
     * @param mixed $methodName
     */
    public function callPrivateMethod($object, $methodName)
    {
        $reflectionClass = new ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $reflectionMethod->setAccessible(true);

        $params = array_slice(func_get_args(), 2); //get all the parameters after $methodName

        return $reflectionMethod->invokeArgs($object, $params);
    }
}
