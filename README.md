# Safeguard - PHP

A simple application that parses through a config.yml file, to backup database (mysql, sqlite, pgsql, dblib) and to archive files in tar.gz

[![Build Status](https://travis-ci.org/spotlab/safeguard.png?branch=master)](https://travis-ci.org/spotlab/safeguard)
[![Latest Stable Version](https://poser.pugx.org/spotlab/safeguard/v/stable.png)](https://packagist.org/packages/spotlab/safeguard)

## Requirements

- PHP 5 >= 5.3.0
- PDO

## Getting started

#### Composer

```
{
    "require": {
        "spotlab/safeguard": "1.*"
    },
    "config": {
        "bin-dir": "bin"
    }
}
```

#### Create config.yml

    projetA:
        database:
            keep_backups: 10
            driver: mysql
            host: 127.0.0.1
            name: projetA
            user: projetA
            password: t2eV9hOVPKzXly3tKZau
            compress: GZIP
            # include_tables:
            #     - table1
            #     - table2
            # exclude_tables:
            #     - table1
            #     - table2
            # no_data: false
            # add_drop_database: false
            # add_drop_table: false
            # single_transaction: false
            # lock_tables: false
            # add_locks: true
            # extended_insert: true
            # disable_keys: true
            # where: ''
            # no_create_info: false
            # skip_triggers: false
            # add_drop_trigger: true
            # hex_blob: true
            backup_file_prefix: false
            backup_path: /home/admin/backup/projetA
            backup_file_prefix: projetA_
        archive:
            keep_backups: 10
            # minsize: >= 10k
            # maxsize: <= 2G
            # exclude_folders:
            #     - /home/admin/www/projetA/current/web/assets/CACHE
            #     - /home/admin/www/projetA/current/web/assets/exclude
            # exclude_files:
            #     - myfilename.gif
            #     - .jpg
            #     - .exe
            folders:
                - /home/admin/www/projetA/current/web/assets
                - /home/admin/www/projetA/current/web/uploads
            backup_path: /home/admin/backup/projetA
            backup_file_prefix: projetA_

    projetB:
        database:
            keep_backups: 10
            name: projetB
            user: projetB
            password: zXly3tKZaut2eV9hOVPK
            compress: None
            backup_path: /home/admin/backup/projetB
            backup_file_prefix: projetB_

    projetC:
        archive:
            keep_backups: 10
            folders:
                - /home/admin/www/projetC/current/web/assets
            backup_path: /home/admin/backup/projetC
            backup_file_prefix: projetC_

#### Start backup command

```
bin/safeguard backup config.yml
```

#### Start restore SQL command

```
# Restore the last backup file
bin/safeguard restore config.yml --project=projetA

# Restore a specific backup file
bin/safeguard restore config.yml --project=projetA --file=/home/admin/backup/projetA/projetA_20141111_214647.sql.gz
```

## Contributing

Format all code to PHP-FIG standards.
http://www.php-fig.org/

## License

This project is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
