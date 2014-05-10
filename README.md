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
            driver: mysql
            host: 127.0.0.1
            name: projetA
            user: projetA
            password: t2eV9hOVPKzXly3tKZau
            # include_tables:
            #     - table1
            #     - table2
            # exclude_tables:
            #     - table1
            #     - table2
            compress: GZIP
            # no_data: false
            # add_drop_database: false
            # add_drop_table: false
            # single_transaction: false
            # lock_tables: false
            # add_locks: false
            # extended_insert: false
            # disable_foreign_keys_check: false
            backup_path: /home/admin/backup/projetA
            backup_file_prefix: projetA_
        archive:
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
            name: projetB
            user: projetB
            password: zXly3tKZaut2eV9hOVPK
            backup_path: /home/admin/backup/projetB
            backup_file_prefix: projetB_

    projetC:
        archive:
            folders:
                - /home/admin/www/projetC/current/web/assets
            backup_path: /home/admin/backup/projetC
            backup_file_prefix: projetC_

#### Start command

    bin/safeguard backup config.yml

## Contributing

Format all code to PHP-FIG standards.
http://www.php-fig.org/

## License

This project is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
