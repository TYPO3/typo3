<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Urs Gehrig <urs@circle.ch>                                  |
// |          Mika Tuupola <tuupola@appelsiini.net>                       |
// | Maintainer: Daniel Convissor <danielc@php.net>                       |
// +----------------------------------------------------------------------+
//
// $Id$


// SQLite function set:
//   sqlite_open, sqlite_popen, sqlite_close, sqlite_query
//   sqlite_libversion, sqlite_libencoding, sqlite_changes
//   sqlite_num_rows, sqlite_num_fields, sqlite_field_name, sqlite_seek
//   sqlite_escape_string, sqlite_busy_timeout, sqlite_last_error
//   sqlite_error_string, sqlite_unbuffered_query, sqlite_create_aggregate
//   sqlite_create_function, sqlite_last_insert_rowid, sqlite_fetch_array
//
// Formatting:
//   # astyle --style=kr < sqlite.php > out.php
//
// Status:
//   EXPERIMENTAL


/*
 * Example:
 *
<?php

    require_once 'DB.php';

    // Define a DSN
    $dsn = 'sqlite://dummy:@localhost/' . getcwd() . '/test.db?mode=0644';

    $db = DB::connect($dsn, false);

    // Give a new table name
    $table = 'tbl_' .  md5(uniqid(rand()));
    $table = substr($table, 0, 10);

    // Create a new table
    $result = $db->query("CREATE TABLE $table (comment varchar(50),
      datetime varchar(50));");
    $result = $db->query("INSERT INTO $table VALUES ('Date and Time', '" .
      date('Y-m-j H:i:s') . "');");

    // Get results
    printf("affectedRows:\t\t%s\n", $db->affectedRows());
    $result = $db->query("SELECT FROM $table;");
    $arr = $db->fetchRow($result);
    print_r($arr);
    $db->disconnect();
?>
 *
 */

require_once 'DB/common.php';

/**
 * Database independent query interface definition for the SQLite
 * PECL extension.
 *
 * @package  DB
 * @version  $Id$
 * @category Database
 * @author   Urs Gehrig <urs@circle.ch>
 * @author   Mika Tuupola <tuupola@appelsiini.net>
 */
class DB_sqlite extends DB_common
{
    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $prepare_tokens = array();
    var $prepare_types = array();
    var $_lasterror = '';

    // }}}
    // {{{ constructor

    /**
     * Constructor for this class.
     *
     * Error codes according to sqlite_exec.  Error Codes specification is
     * in the {@link http://sqlite.org/c_interface.html online manual}.
     *
     * This errorhandling based on sqlite_exec is not yet implemented.
     *
     * @access public
     */
    function DB_sqlite()
    {

        $this->DB_common();
        $this->phptype = 'sqlite';
        $this->dbsyntax = 'sqlite';
        $this->features = array (
            'prepare' => false,
            'pconnect' => true,
            'transactions' => false,
            'limit' => 'alter'
        );

        // SQLite data types, http://www.sqlite.org/datatypes.html
        $this->keywords = array (
            'BLOB'      => '',
            'BOOLEAN'   => '',
            'CHARACTER' => '',
            'CLOB'      => '',
            'FLOAT'     => '',
            'INTEGER'   => '',
            'KEY'       => '',
            'NATIONAL'  => '',
            'NUMERIC'   => '',
            'NVARCHAR'  => '',
            'PRIMARY'   => '',
            'TEXT'      => '',
            'TIMESTAMP' => '',
            'UNIQUE'    => '',
            'VARCHAR'   => '',
            'VARYING'   => ''
        );
        $this->errorcode_map = array(
            1  => DB_ERROR_SYNTAX,
            19 => DB_ERROR_CONSTRAINT,
            20 => DB_ERROR_MISMATCH,
            23 => DB_ERROR_ACCESS_VIOLATION
        );
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to a database represented by a file.
     *
     * @param $dsn the data source name; the file is taken as
     *        database; "sqlite://root:@host/test.db?mode=0644"
     * @param $persistent (optional) whether the connection should
     *        be persistent
     * @access public
     * @return int DB_OK on success, a DB error on failure
     */
    function connect($dsninfo, $persistent = false)
    {
        if (!DB::assertExtension('sqlite')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }

        if ($dsninfo['database']) {
            if (!file_exists($dsninfo['database'])) {
                if (!touch($dsninfo['database'])) {
                    return $this->sqliteRaiseError(DB_ERROR_NOT_FOUND);
                }
                if (!isset($dsninfo['mode'])
                        || !is_numeric($dsninfo['mode'])) {
                    $mode = 0644;
                } else {
                    $mode = octdec($dsninfo['mode']);
                }
                if (!chmod($dsninfo['database'], $mode)) {
                    return $this->sqliteRaiseError(DB_ERROR_NOT_FOUND);
                }
                if (!file_exists($dsninfo['database'])) {
                    return $this->sqliteRaiseError(DB_ERROR_NOT_FOUND);
                }
            }
            if (!is_file($dsninfo['database'])) {
                return $this->sqliteRaiseError(DB_ERROR_INVALID);
            }
            if (!is_readable($dsninfo['database'])) {
                return $this->sqliteRaiseError(DB_ERROR_ACCESS_VIOLATION);
            }
        } else {
            return $this->sqliteRaiseError(DB_ERROR_ACCESS_VIOLATION);
        }

        $connect_function = $persistent ? 'sqlite_popen' : 'sqlite_open';
        if (!($conn = @$connect_function($dsninfo['database']))) {
            return $this->sqliteRaiseError(DB_ERROR_NODBSELECTED);
        }
        $this->connection = $conn;

        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @access public
     * @return bool TRUE on success, FALSE if not connected.
     * @todo fix return values
     */
    function disconnect()
    {
        $ret = @sqlite_close($this->connection);
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Send a query to SQLite and returns the results as a SQLite resource
     * identifier.
     *
     * @param the SQL query
     * @access public
     * @return mixed returns a valid SQLite result for successful SELECT
     * queries, DB_OK for other successful queries. A DB error is
     * returned on failure.
     */
    function simpleQuery($query)
    {
        $ismanip = DB::isManip($query);
        $this->last_query = $query;
        $query = $this->_modifyQuery($query);
        ini_set('track_errors', true);
        $result = @sqlite_query($query, $this->connection);
        ini_restore('track_errors');
        $this->_lasterror = isset($php_errormsg) ? $php_errormsg : '';
        $this->result = $result;
        if (!$this->result) {
            return $this->sqliteRaiseError(null);
        }

        /* sqlite_query() seems to allways return a resource */
        /* so cant use that. Using $ismanip instead          */
        if (!$ismanip) {
            $numRows = $this->numRows($result);

            /* if numRows() returned PEAR_Error */
            if (is_object($numRows)) {
                return $numRows;
            }
            return $result;
        }
        return DB_OK;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal sqlite result pointer to the next available result.
     *
     * @param a valid sqlite result resource
     * @access public
     * @return true if a result is available otherwise return false
     */
    function nextResult($result)
    {
        return false;
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * Formating of the array and the data therein are configurable.
     * See DB_result::fetchInto() for more information.
     *
     * @param resource $result    query result identifier
     * @param array    $arr       (reference) array where data from the row
     *                            should be placed
     * @param int      $fetchmode how the resulting array should be indexed
     * @param int      $rownum    the row number to fetch
     *
     * @return mixed DB_OK on success, NULL when end of result set is
     *               reached or on failure
     *
     * @see DB_result::fetchInto()
     * @access private
     */
    function fetchInto($result, &$arr, $fetchmode, $rownum=null)
    {
        if ($rownum !== null) {
            if (!@sqlite_seek($this->result, $rownum)) {
                return null;
            }
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $arr = sqlite_fetch_array($result, SQLITE_ASSOC);
            if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE && $arr) {
                $arr = array_change_key_case($arr, CASE_LOWER);
            }
        } else {
            $arr = sqlite_fetch_array($result, SQLITE_NUM);
        }
        if (!$arr) {
            /* See: http://bugs.php.net/bug.php?id=22328 */
            return null;
        }
        if ($this->options['portability'] & DB_PORTABILITY_RTRIM) {
            /*
             * Even though this DBMS already trims output, we do this because
             * a field might have intentional whitespace at the end that
             * gets removed by DB_PORTABILITY_RTRIM under another driver.
             */
            $this->_rtrimArrayValues($arr);
        }
        return DB_OK;
    }

    // }}}
    // {{{ freeResult()

    /**
     * Free the internal resources associated with $result.
     *
     * @param $result SQLite result identifier
     * @access public
     * @return bool TRUE on success, FALSE if $result is invalid
     */
    function freeResult(&$result)
    {
        // XXX No native free?
        if (!is_resource($result)) {
            return false;
        }
        $result = null;
        return true;
    }

    // }}}
    // {{{ numCols()

    /**
     * Gets the number of columns in a result set.
     *
     * @return number of columns in a result set
     */
    function numCols($result)
    {
        $cols = @sqlite_num_fields($result);
        if (!$cols) {
            return $this->sqliteRaiseError();
        }
        return $cols;
    }

    // }}}
    // {{{ numRows()

    /**
     * Gets the number of rows affected by a query.
     *
     * @return number of rows affected by the last query
     */
    function numRows($result)
    {
        $rows = @sqlite_num_rows($result);
        if (!is_integer($rows)) {
            return $this->raiseError();
        }
        return $rows;
    }

    // }}}
    // {{{ affected()

    /**
     * Gets the number of rows affected by a query.
     *
     * @return number of rows affected by the last query
     */
    function affectedRows()
    {
        return sqlite_changes($this->connection);
    }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error string of the last error (if any) that
     * occured on the current connection.
     *
     * This is used to retrieve more meaningfull error messages DB_pgsql
     * way since sqlite_last_error() does not provide adequate info.
     *
     * @return string native SQLite error message
     */
    function errorNative()
    {
        return($this->_lasterror);
    }

    // }}}
    // {{{ errorCode()

    /**
     * Determine PEAR::DB error code from the database's text error message.
     *
     * @param  string  $errormsg  error message returned from the database
     * @return integer  an error number from a DB error constant
     */
    function errorCode($errormsg)
    {
        static $error_regexps;
        if (!isset($error_regexps)) {
            $error_regexps = array(
                '/^no such table:/' => DB_ERROR_NOSUCHTABLE,
                '/^table .* already exists$/' => DB_ERROR_ALREADY_EXISTS,
                '/PRIMARY KEY must be unique/i' => DB_ERROR_CONSTRAINT,
                '/uniqueness constraint failed/' => DB_ERROR_CONSTRAINT,
                '/^no such column:/' => DB_ERROR_NOSUCHFIELD,
                '/^near ".*": syntax error$/' => DB_ERROR_SYNTAX
            );
        }
        foreach ($error_regexps as $regexp => $code) {
            if (preg_match($regexp, $errormsg)) {
                return $code;
            }
        }
        // Fall back to DB_ERROR if there was no mapping.
        return DB_ERROR;
    }

    // }}}
    // {{{ dropSequence()

    /**
     * Deletes a sequence
     *
     * @param string $seq_name  name of the sequence to be deleted
     *
     * @return int  DB_OK on success.  DB_Error if problems.
     *
     * @internal
     * @see DB_common::dropSequence()
     * @access public
     */
    function dropSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        return $this->query("DROP TABLE $seqname");
    }

    /**
     * Creates a new sequence
     *
     * @param string $seq_name  name of the new sequence
     *
     * @return int  DB_OK on success.  A DB_Error object is returned if
     *              problems arise.
     *
     * @internal
     * @see DB_common::createSequence()
     * @access public
     */
    function createSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        $query   = 'CREATE TABLE ' . $seqname .
                   ' (id INTEGER UNSIGNED PRIMARY KEY) ';
        $result  = $this->query($query);
        if (DB::isError($result)) {
            return($result);
        }
        $query   = "CREATE TRIGGER ${seqname}_cleanup AFTER INSERT ON $seqname
                    BEGIN
                        DELETE FROM $seqname WHERE id<LAST_INSERT_ROWID();
                    END ";
        $result  = $this->query($query);
        if (DB::isError($result)) {
            return($result);
        }
    }

    // }}}
    // {{{ nextId()

    /**
     * Returns the next free id in a sequence
     *
     * @param string  $seq_name  name of the sequence
     * @param boolean $ondemand  when true, the seqence is automatically
     *                           created if it does not exist
     *
     * @return int  the next id number in the sequence.  DB_Error if problem.
     *
     * @internal
     * @see DB_common::nextID()
     * @access public
     */
    function nextId($seq_name, $ondemand = true)
    {
        $seqname = $this->getSequenceName($seq_name);

        do {
            $repeat = 0;
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $result = $this->query("INSERT INTO $seqname VALUES (NULL)");
            $this->popErrorHandling();
            if ($result == DB_OK) {
                $id = sqlite_last_insert_rowid($this->connection);
                if ($id != 0) {
                    return $id;
                }
            } elseif ($ondemand && DB::isError($result) &&
            $result->getCode() == DB_ERROR_NOSUCHTABLE) {
                $result = $this->createSequence($seq_name);
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                } else {
                    $repeat = 1;
                }
            }
        } while ($repeat);

        return $this->raiseError($result);
    }

    // }}}
    // {{{ getSpecialQuery()

    /**
     * Returns the query needed to get some backend info.
     *
     * Refer to the online manual at http://sqlite.org/sqlite.html.
     *
     * @param string $type What kind of info you want to retrieve
     * @return string The SQL query string
     */
    function getSpecialQuery($type, $args=array())
    {
        if(!is_array($args))
            return $this->raiseError('no key specified', null, null, null,
                                     'Argument has to be an array.');
        switch (strtolower($type)) {
            case 'master':
                return 'SELECT * FROM sqlite_master;';
            case 'tables':
                return "SELECT name FROM sqlite_master WHERE type='table' "
                       . 'UNION ALL SELECT name FROM sqlite_temp_master '
                       . "WHERE type='table' ORDER BY name;";
            case 'schema':
                return 'SELECT sql FROM (SELECT * FROM sqlite_master UNION ALL '
                       . 'SELECT * FROM sqlite_temp_master) '
                       . "WHERE type!='meta' ORDER BY tbl_name, type DESC, name;";
            case 'schemax':
            case 'schema_x':
                /*
                 * Use like:
                 * $res = $db->query($db->getSpecialQuery('schema_x', array('table' => 'table3')));
                 */
                return 'SELECT sql FROM (SELECT * FROM sqlite_master UNION ALL '
                       . 'SELECT * FROM sqlite_temp_master) '
                       . "WHERE tbl_name LIKE '{$args['table']}' AND type!='meta' "
                       . 'ORDER BY type DESC, name;';
            case 'alter':
                /*
                 * SQLite does not support ALTER TABLE; this is a helper query
                 * to handle this. 'table' represents the table name, 'rows'
                 * the news rows to create, 'save' the row(s) to keep _with_
                 * the data.
                 *
                 * Use like:
                 * $args = array(
                 *     'table' => $table,
                 *     'rows'  => "id INTEGER PRIMARY KEY, firstname TEXT, surname TEXT, datetime TEXT",
                 *     'save'  => "NULL, titel, content, datetime"
                 * );
                 * $res = $db->query( $db->getSpecialQuery('alter', $args));
                 */
                $rows = strtr($args['rows'], $this->keywords);

                $q = array(
                    'BEGIN TRANSACTION',
                    "CREATE TEMPORARY TABLE {$args['table']}_backup ({$args['rows']})",
                    "INSERT INTO {$args['table']}_backup SELECT {$args['save']} FROM {$args['table']}",
                    "DROP TABLE {$args['table']}",
                    "CREATE TABLE {$args['table']} ({$args['rows']})",
                    "INSERT INTO {$args['table']} SELECT {$rows} FROM {$args['table']}_backup",
                    "DROP TABLE {$args['table']}_backup",
                    'COMMIT',
                );

                // This is a dirty hack, since the above query will no get executed with a single
                // query call; so here the query method will be called directly and return a select instead.
                foreach($q as $query) {
                    $this->query($query);
                }
                return "SELECT * FROM {$args['table']};";
            default:
                return null;
        }
    }

    // }}}
    // {{{ getDbFileStats()

    /**
     * Get the file stats for the current database.
     *
     * Possible arguments are dev, ino, mode, nlink, uid, gid, rdev, size,
     * atime, mtime, ctime, blksize, blocks or a numeric key between
     * 0 and 12.
     *
     * @param string $arg Array key for stats()
     * @return mixed array on an unspecified key, integer on a passed arg and
     * FALSE at a stats error.
     */
    function getDbFileStats($arg = '')
    {
        $stats = stat($this->dsn['database']);
        if ($stats == false)
            return false;
        if (is_array($stats)) {
            if(is_numeric($arg)) {
                if(((int)$arg <= 12) & ((int)$arg >= 0))
                    return false;
                return $stats[$arg ];
            }
            if (array_key_exists(trim($arg), $stats)) {
                return $stats[$arg ];
            }
        }
        return $stats;
    }

    // }}}
    // {{{ modifyLimitQuery()

    function modifyLimitQuery($query, $from, $count)
    {
        $query = $query . " LIMIT $count OFFSET $from";
        return $query;
    }

    // }}}
    // {{{ modifyQuery()

    /**
     * "DELETE FROM table" gives 0 affected rows in SQLite.
     *
     * This little hack lets you know how many rows were deleted.
     *
     * @param string $query The SQL query string
     * @return string The SQL query string
     */
    function _modifyQuery($query)
    {
        if ($this->options['portability'] & DB_PORTABILITY_DELETE_COUNT) {
            if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $query)) {
                $query = preg_replace('/^\s*DELETE\s+FROM\s+(\S+)\s*$/',
                                      'DELETE FROM \1 WHERE 1=1', $query);
            }
        }
        return $query;
    }

    // }}}
    // {{{ sqliteRaiseError()

    /**
     * Gather information about an error, then use that info to create a
     * DB error object and finally return that object.
     *
     * @param  integer  $errno  PEAR error number (usually a DB constant) if
     *                          manually raising an error
     * @return object  DB error object
     * @see errorNative()
     * @see errorCode()
     * @see DB_common::raiseError()
     */
    function sqliteRaiseError($errno = null)
    {

        $native = $this->errorNative();
        if ($errno === null) {
            $errno = $this->errorCode($native);
        }

        $errorcode = @sqlite_last_error($this->connection);
        $userinfo = "$errorcode ** $this->last_query";

        return $this->raiseError($errno, null, null, $userinfo, $native);
    }

    // }}}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */

?>
