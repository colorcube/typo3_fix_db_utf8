#!/usr/bin/php -q
<?php

/*
 * This script is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */


/**
 * This is a cli script:
 *
 * Fix Database Encoding (to UTF8)
 *
 * see description() for more information
 *
 * @author Rene Fritz <r.fritz@colorcube.de>
 * @since  2016
 * @see <https://github.com/colorcube/typo3_fix_db_utf8/>
 */
class fix_db_utf8 {

    protected $encodings = [
        ['big5','Big5 Traditional Chinese'],
        ['dec8','DEC West European'],
        ['cp850','DOS West European'],
        ['hp8','HP West European'],
        ['koi8r','KOI8-R Relcom Russian'],
        ['latin1','cp1252 West European'],
        ['latin2','ISO 8859-2 Central European'],
        ['swe7','7bit Swedish'],
        ['ascii','US ASCII'],
        ['ujis','EUC-JP Japanese'],
        ['sjis','Shift-JIS Japanese'],
        ['hebrew','ISO 8859-8 Hebrew'],
        ['tis620','TIS620 Thai'],
        ['euckr','EUC-KR Korean'],
        ['koi8u','KOI8-U Ukrainian'],
        ['gb2312','GB2312 Simplified Chinese'],
        ['greek','ISO 8859-7 Greek'],
        ['cp1250','Windows Central European'],
        ['gbk','GBK Simplified Chinese'],
        ['latin5','ISO 8859-9 Turkish'],
        ['armscii8','ARMSCII-8 Armenian'],
        ['utf8','UTF-8 Unicode'],
        ['ucs2','UCS-2 Unicode'],
        ['cp866','DOS Russian'],
        ['keybcs2','DOS Kamenicky Czech-Slovak'],
        ['macce','Mac Central European'],
        ['macroman','Mac West European'],
        ['cp852','DOS Central European'],
        ['latin7','ISO 8859-13 Baltic'],
        ['utf8mb4','UTF-8 Unicode'],
        ['cp1251','Windows Cyrillic'],
        ['utf16','UTF-16 Unicode'],
        ['utf16le','UTF-16LE Unicode'],
        ['cp1256','Windows Arabic'],
        ['cp1257','Windows Baltic'],
        ['utf32','UTF-32 Unicode'],
        ['binary','Binary pseudo charset'],
        ['geostd8','GEOSTD8 Georgian'],
        ['cp932','SJIS for Windows Japanese'],
        ['eucjpms','UJIS for Windows Japanese'],
        ['gb18030','China National Standard GB18030']
    ];


    /**
     * starts the cli
     */
    public function __construct()
    {
        if ($_SERVER['argc'] == 1){
            $this->printUsageDescription();
            exit (1);
        }

        $db_username = null;
        $db_password = null;
        $db_name = null;

        // optional
        $source_encoding = null;

        # [-e source_encoding] -u db_username -p db_password -d database

        $opts = getopt('hle::u:p:d:');

        // Handle command line arguments
        foreach (array_keys($opts) as $opt) switch ($opt) {
            case 'u':
                $db_username = $opts['u'];
                break;
            case 'p':
                $db_password = $opts['p'];
                break;
            case 'd':
                $db_name = $opts['d'];
                break;
            case 'e':
                $source_encoding = $opts['e'];
                break;

            case 'h':
                $this->printUsageDescription();
                exit(1);

            case 'l':
                $this->listEncodings();
                exit(1);
        }


        if (!isset($db_username) || !isset($db_password) || !isset($db_name)) {
            echo "Please provide database username, password and database name!\n\n";
            $this->printUsageDescription();
            exit (1);
        }


        $this->process($db_username, $db_password, $db_name, $source_encoding);

        exit (0);
    }


    protected function printUsageDescription()
    {
        $this->usage();
        echo "\n";
        $this->description();
    }


    /**
     * print description of cli
     */
    protected function description()
    {
        echo "\nFix Database Encoding (to UTF8)

In the old TYPO3 days other charsets/encodings than utf8 were used. 
Sometimes the encoding of the data is different that the encoding in the database definition. This causes some problems.

This tool convert all data and encoding definitions in the database to utf8. To be able to do so you need to know the current setup.

Variants:
1. the data stored in the database is encoded with an encoding different from utf8 (eg. latin1)
2. the data stored in the database is encoded in utf8 (because TYPO3 was configured with forceCharset to use utf8) 
   but the database encoding definition for tables and fields is set to xx encoding (non utf8, eg. latin1_swedish_ci)
3. some other weird setup

1. and 2. can be fixed with this tool.

Fixing 1.
Let's say the data stored in the database is known to use the latin1 encoding. 
" . basename($_SERVER['argv'][0]) . " -e latin1 -u db_username -p db_password -d database

Fixing 2. 
" . basename($_SERVER['argv'][0]) . " -u db_username -p db_password -d database

WARNING: Make a backup of your database before using this tool. Messed up encodings can be tricky. This tool might make it even worse.\n";
    }


    /**
     * print usage message
     */
    protected function usage()
    {
        echo "\nusage: " . basename($_SERVER['argv'][0]) . " [-e source_encoding] -u db_username -p db_password -d database\n
        -h for help
        -l for list of encodings";
    }



    /**
     * list encodings
     */
    protected function listEncodings()
    {
        echo "Encodings:\n";
        foreach ($this->encodings as $encoding) {
            echo "{$encoding[0]}\t{$encoding[1]}\n";
        }
    }


    /**
     * Generates the module content
     *
     * @return	void
     */
    protected function process($db_username, $db_password, $db_name, $source_encoding=null)
    {
        $this->connectDB($db_username, $db_password, $db_name);

        $this->sql_query('ALTER DATABASE `' . $db_name . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci');

        $tables = $this->admin_get_tables($db_name);

        foreach ($tables as $table => $info) {

            $this->sql_query('ALTER TABLE `' . $table . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci');
            echo $this->sql_error();

            echo "Table '{$table}' is setup to use utf8.\n";

            $fields = $this->admin_get_fields($table);

            $fieldChanged = false;
            foreach ($fields as $name => $field) {

                if ((substr($field['Collation'], 0, 4) != 'utf8') AND (stristr($field['Type'], 'char') OR stristr($field['Type'], 'text'))) {

                    $fieldChanged = true;

                    // this will make the DB ignore any current encoding
                    $this->sql_query('ALTER TABLE `' . $table . '` CHANGE `' . $name . '` `' . $name . '` ' . $field['Type'] . ' CHARACTER SET binary');
                    echo $this->sql_error();

                    if ($source_encoding) {
                        // set the right encoding for the data that exists in db
                        $this->sql_query('ALTER TABLE `' . $table . '` CHANGE `' . $name . '` `' . $name . '` ' . $field['Type'] . ' CHARACTER SET ' . $source_encoding);
                        echo $this->sql_error();
                    }

                    // set the new encoding - will convert data if encoding of field is not 'binary'
                    $this->sql_query('ALTER TABLE `' . $table . '` CHANGE `' . $name . '` `' . $name . '` ' . $field['Type'] . ' CHARACTER SET utf8 COLLATE utf8_unicode_ci');
                    echo $this->sql_error();
                }
            }

            if ($fieldChanged) {
                if ($source_encoding) {
                    echo "Current data in fields of table '{$table}' are converted from '{$source_encoding}' to utf8.\n";
                } else {
                    echo "Fields of table '{$table}' are setup to use utf8.\n";
                }
            }
        }

        echo "Database is setup to use utf8 now.\n";
        echo "Setup finished.\n";
    }


    /**************************************
     *
     * MySQL(i) wrapper functions borrowed from TYPO3
     *
     **************************************/


    /**
     * @var \mysqli $link Default database link object
     */
    protected $link = null;

    /**
     * Executes query
     * MySQLi query() wrapper function
     * Beware: Use of this method should be avoided as it is experimentally supported by DBAL. You should consider
     * using exec_SELECTquery() and similar methods instead.
     *
     * @param string $query Query to execute
     * @return bool|\mysqli_result|object MySQLi result object / DBAL object
     */
    public function sql_query($query)
    {
        return $this->link->query($query);
    }

    /**
     * Returns the error status on the last query() execution
     *
     * @return string MySQLi error string.
     */
    public function sql_error()
    {
        return $this->link->error;
    }

    /**
     * Returns the error number on the last query() execution
     *
     * @return int MySQLi error number
     */
    public function sql_errno()
    {
        return $this->link->errno;
    }

    /**
     * Returns the list of tables from the default database, TYPO3_db (quering the DBMS)
     * In a DBAL this method should 1) look up all tables from the DBMS  of
     * the _DEFAULT handler and then 2) add all tables *configured* to be managed by other handlers
     *
     * @return array Array with tablenames as key and arrays with status information as value
     */
    public function admin_get_tables($db_name)
    {
        $whichTables = [];
        $tables_result = $this->sql_query('SHOW TABLE STATUS FROM `' . $db_name . '`');
        if ($tables_result !== false) {
            while ($theTable = $tables_result->fetch_assoc()) {
                $whichTables[$theTable['Name']] = $theTable;
            }
            $tables_result->free();
        }
        return $whichTables;
    }

    /**
     * Returns information about each field in the $table (quering the DBMS)
     * In a DBAL this should look up the right handler for the table and return compatible information
     * This function is important not only for the Install Tool but probably for
     * DBALs as well since they might need to look up table specific information
     * in order to construct correct queries. In such cases this information should
     * probably be cached for quick delivery.
     *
     * @param string $tableName Table name
     * @return array Field information in an associative array with fieldname => field row
     */
    public function admin_get_fields($tableName)
    {
        $output = [];
        $columns_res = $this->sql_query('SHOW FULL COLUMNS FROM `' . $tableName . '`');
        if ($columns_res !== false) {
            while ($fieldRow = $columns_res->fetch_assoc()) {
                $output[$fieldRow['Field']] = $fieldRow;
            }
            $columns_res->free();
        }
        return $output;
    }

    /**
     * Connects to database for TYPO3 sites:
     *
     * @return void
     */
    public function connectDB($db_username, $db_password, $db_name)
    {
        $this->sql_pconnect($db_username, $db_password);
        $this->sql_select_db($db_name);
    }

    /**
     * Open a (persistent) connection to a MySQL server
     *
     * @return bool|void
     * @throws \RuntimeException
     */
    public function sql_pconnect($db_username, $db_password)
    {
        if (!extension_loaded('mysqli')) {
            throw new \RuntimeException('Database Error: PHP mysqli extension not loaded.');
        }

        $host = 'localhost';

        // TODO
        $databasePort = '';
        $databaseSocket = '';

        $this->link = mysqli_init();
        $connected = $this->link->real_connect(
            $host,
            $db_username,
            $db_password
            // TODO
            //            ,
            //            NULL,
            //            (int)$databasePort,
            //            $databaseSocket,
            //            0
        );

        if (!$connected) {
            throw new \RuntimeException('Fatal Error: The current username, password or host was not accepted when the connection to the database was attempted to be established!');
        }
    }

    /**
     * Select a SQL database
     *
     * @return bool|void
     * @throws \RuntimeException
     */
    public function sql_select_db($db_name)
    {
        $ret = $this->link->select_db($db_name);
        if (!$ret) {
            throw new \RuntimeException('Fatal Error: Cannot connect to the current database, "' . $db_name . '"!');
        }
    }

}





$cli = new fix_db_utf8;
