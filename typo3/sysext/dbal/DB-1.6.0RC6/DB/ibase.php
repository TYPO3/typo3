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
// | Author: Sterling Hughes <sterling@php.net>                           |
// | Maintainer: Daniel Convissor <danielc@php.net>                       |
// +----------------------------------------------------------------------+
//
// $Id$


// Bugs:
//  - If dbsyntax is not firebird, the limitQuery may fail


require_once 'DB/common.php';

/**
 * Database independent query interface definition for PHP's Interbase
 * extension.
 *
 * @package  DB
 * @version  $Id$
 * @category Database
 * @author   Sterling Hughes <sterling@php.net>
 */
class DB_ibase extends DB_common
{

    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $autocommit = 1;
    var $manip_query = array();

    // }}}
    // {{{ constructor

    function DB_ibase()
    {
        $this->DB_common();
        $this->phptype = 'ibase';
        $this->dbsyntax = 'ibase';
        $this->features = array(
            'prepare' => true,
            'pconnect' => true,
            'transactions' => true,
            'limit' => false
        );
        // just a few of the tons of Interbase error codes listed in the
        // Language Reference section of the Interbase manual
        $this->errorcode_map = array(
            -104 => DB_ERROR_SYNTAX,
            -150 => DB_ERROR_ACCESS_VIOLATION,
            -151 => DB_ERROR_ACCESS_VIOLATION,
            -155 => DB_ERROR_NOSUCHTABLE,
            88   => DB_ERROR_NOSUCHTABLE,
            -157 => DB_ERROR_NOSUCHFIELD,
            -158 => DB_ERROR_VALUE_COUNT_ON_ROW,
            -170 => DB_ERROR_MISMATCH,
            -171 => DB_ERROR_MISMATCH,
            -172 => DB_ERROR_INVALID,
            -204 => DB_ERROR_INVALID,
            -205 => DB_ERROR_NOSUCHFIELD,
            -206 => DB_ERROR_NOSUCHFIELD,
            -208 => DB_ERROR_INVALID,
            -219 => DB_ERROR_NOSUCHTABLE,
            -297 => DB_ERROR_CONSTRAINT,
            -530 => DB_ERROR_CONSTRAINT,
            -607 => DB_ERROR_NOSUCHTABLE,
            -803 => DB_ERROR_CONSTRAINT,
            -551 => DB_ERROR_ACCESS_VIOLATION,
            -552 => DB_ERROR_ACCESS_VIOLATION,
            -922 => DB_ERROR_NOSUCHDB,
            -923 => DB_ERROR_CONNECT_FAILED,
            -924 => DB_ERROR_CONNECT_FAILED
        );
    }

    // }}}
    // {{{ connect()

    function connect($dsninfo, $persistent = false)
    {
        if (!DB::assertExtension('interbase')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }
        $this->dsn = $dsninfo;
        $dbhost = $dsninfo['hostspec'] ?
                  ($dsninfo['hostspec'] . ':' . $dsninfo['database']) :
                  $dsninfo['database'];

        $connect_function = $persistent ? 'ibase_pconnect' : 'ibase_connect';

        $params = array();
        $params[] = $dbhost;
        $params[] = $dsninfo['username'] ? $dsninfo['username'] : null;
        $params[] = $dsninfo['password'] ? $dsninfo['password'] : null;
        $params[] = isset($dsninfo['charset']) ? $dsninfo['charset'] : null;
        $params[] = isset($dsninfo['buffers']) ? $dsninfo['buffers'] : null;
        $params[] = isset($dsninfo['dialect']) ? $dsninfo['dialect'] : null;
        $params[] = isset($dsninfo['role'])    ? $dsninfo['role'] : null;

        $conn = @call_user_func_array($connect_function, $params);
        if (!$conn) {
            return $this->ibaseRaiseError(DB_ERROR_CONNECT_FAILED);
        }
        $this->connection = $conn;
        if ($this->dsn['dbsyntax'] == 'firebird') {
            $this->features['limit'] = 'alter';
        }
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    function disconnect()
    {
        $ret = @ibase_close($this->connection);
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ simpleQuery()

    function simpleQuery($query)
    {
        $ismanip = DB::isManip($query);
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        $result = @ibase_query($this->connection, $query);
        if (!$result) {
            return $this->ibaseRaiseError();
        }
        if ($this->autocommit && $ismanip) {
            ibase_commit($this->connection);
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        return $ismanip ? DB_OK : $result;
    }

    // }}}
    // {{{ modifyLimitQuery()

    /**
     * This method is used by backends to alter limited queries
     * Uses the new FIRST n SKIP n Firebird 1.0 syntax, so it is
     * only compatible with Firebird 1.x
     *
     * @param string  $query query to modify
     * @param integer $from  the row to start to fetching
     * @param integer $count the numbers of rows to fetch
     *
     * @return the new (modified) query
     * @author Ludovico Magnocavallo <ludo@sumatrasolutions.com>
     * @access private
     */
    function modifyLimitQuery($query, $from, $count)
    {
        if ($this->dsn['dbsyntax'] == 'firebird') {
            //$from++; // SKIP starts from 1, ie SKIP 1 starts from the first record
                           // (cox) Seems that SKIP starts in 0
            $query = preg_replace('/^\s*select\s(.*)$/is',
                                  "SELECT FIRST $count SKIP $from $1", $query);
        }
        return $query;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal ibase result pointer to the next available result
     *
     * @param a valid fbsql result resource
     *
     * @access public
     *
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
        if ($rownum !== NULL) {
            return $this->ibaseRaiseError(DB_ERROR_NOT_CAPABLE);
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            if (function_exists('ibase_fetch_assoc')) {
                $arr = @ibase_fetch_assoc($result);
            } else {
                $arr = get_object_vars(ibase_fetch_object($result));
            }
            if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE && $arr) {
                $arr = array_change_key_case($arr, CASE_LOWER);
            }
        } else {
            $arr = @ibase_fetch_row($result);
        }
        if (!$arr) {
            if ($errmsg = ibase_errmsg()) {
                return $this->ibaseRaiseError(null, $errmsg);
            } else {
                return null;
            }
        }
        if ($this->options['portability'] & DB_PORTABILITY_RTRIM) {
            $this->_rtrimArrayValues($arr);
        }
        return DB_OK;
    }

    // }}}
    // {{{ freeResult()

    function freeResult($result)
    {
        return @ibase_free_result($result);
    }

    // }}}
    // {{{ freeQuery()

    function freeQuery($query)
    {
        ibase_free_query($query);
        return true;
    }

    // }}}
    // {{{ numCols()

    function numCols($result)
    {
        $cols = ibase_num_fields($result);
        if (!$cols) {
            return $this->ibaseRaiseError();
        }
        return $cols;
    }

    // }}}
    // {{{ prepare()

    /**
     * Prepares a query for multiple execution with execute().
     *
     * prepare() requires a generic query as string like <code>
     *    INSERT INTO numbers VALUES (?, ?, ?)
     * </code>.  The <kbd>?</kbd> characters are placeholders.
     *
     * Three types of placeholders can be used:
     *   + <kbd>?</kbd>  a quoted scalar value, i.e. strings, integers
     *   + <kbd>!</kbd>  value is inserted 'as is'
     *   + <kbd>&</kbd>  requires a file name.  The file's contents get
     *                     inserted into the query (i.e. saving binary
     *                     data in a db)
     *
     * Use backslashes to escape placeholder characters if you don't want
     * them to be interpreted as placeholders.  Example: <code>
     *    "UPDATE foo SET col=? WHERE col='over \& under'"
     * </code>
     *
     * @param string $query query to be prepared
     * @return mixed DB statement resource on success. DB_Error on failure.
     */
    function prepare($query)
    {
        $tokens   = preg_split('/((?<!\\\)[&?!])/', $query, -1,
                               PREG_SPLIT_DELIM_CAPTURE);
        $token    = 0;
        $types    = array();
        $newquery = '';

        foreach ($tokens as $key => $val) {
            switch ($val) {
                case '?':
                    $types[$token++] = DB_PARAM_SCALAR;
                    break;
                case '&':
                    $types[$token++] = DB_PARAM_OPAQUE;
                    break;
                case '!':
                    $types[$token++] = DB_PARAM_MISC;
                    break;
                default:
                    $tokens[$key] = preg_replace('/\\\([&?!])/', "\\1", $val);
                    $newquery .= $tokens[$key] . '?';
            }
        }

        $newquery = substr($newquery, 0, -1);
        $this->last_query = $query;
        $newquery = $this->modifyQuery($newquery);
        $stmt = ibase_prepare($this->connection, $newquery);
        $this->prepare_types[(int)$stmt] = $types;
        $this->manip_query[(int)$stmt]   = DB::isManip($query);
        return $stmt;
    }

    // }}}
    // {{{ execute()

    /**
     * Executes a DB statement prepared with prepare().
     *
     * @param resource  $stmt  a DB statement resource returned from prepare()
     * @param mixed  $data  array, string or numeric data to be used in
     *                      execution of the statement.  Quantity of items
     *                      passed must match quantity of placeholders in
     *                      query:  meaning 1 for non-array items or the
     *                      quantity of elements in the array.
     * @return object  a new DB_Result or a DB_Error when fail
     * @see DB_ibase::prepare()
     * @access public
     */
    function &execute($stmt, $data = array())
    {
        if (!is_array($data)) {
            $data = array($data);
        }

        $types =& $this->prepare_types[$stmt];
        if (count($types) != count($data)) {
            $tmp =& $this->raiseError(DB_ERROR_MISMATCH);
            return $tmp;
        }

        $i = 0;
        foreach ($data as $key => $value) {
            if ($types[$i] == DB_PARAM_MISC) {
                /*
                 * ibase doesn't seem to have the ability to pass a
                 * parameter along unchanged, so strip off quotes from start
                 * and end, plus turn two single quotes to one single quote,
                 * in order to avoid the quotes getting escaped by
                 * ibase and ending up in the database.
                 */
                $data[$key] = preg_replace("/^'(.*)'$/", "\\1", $data[$key]);
                $data[$key] = str_replace("''", "'", $data[$key]);
            } elseif ($types[$i] == DB_PARAM_OPAQUE) {
                $fp = @fopen($data[$key], 'rb');
                if (!$fp) {
                    $tmp =& $this->raiseError(DB_ERROR_ACCESS_VIOLATION);
                    return $tmp;
                }
                $data[$key] = fread($fp, filesize($data[$key]));
                fclose($fp);
            }
            $i++;
        }

        array_unshift($data, $stmt);

        $res = call_user_func_array('ibase_execute', $data);
        if (!$res) {
            $tmp =& $this->ibaseRaiseError();
            return $tmp;
        }
        /* XXX need this?
        if ($this->autocommit && $this->manip_query[(int)$stmt]) {
            ibase_commit($this->connection);
        }*/
        if ($this->manip_query[(int)$stmt]) {
            $tmp = DB_OK;
        } else {
            $tmp =& new DB_result($this, $res);
        }
        return $tmp;
    }

    /**
     * Free the internal resources associated with a prepared query.
     *
     * @param $stmt The interbase_query resource type
     *
     * @return bool TRUE on success, FALSE if $result is invalid
     */
    function freePrepared($stmt)
    {
        if (!is_resource($stmt)) {
            return false;
        }
        ibase_free_query($stmt);
        unset($this->prepare_tokens[(int)$stmt]);
        unset($this->prepare_types[(int)$stmt]);
        unset($this->manip_query[(int)$stmt]);
        return true;
    }

    // }}}
    // {{{ autoCommit()

    function autoCommit($onoff = false)
    {
        $this->autocommit = $onoff ? 1 : 0;
        return DB_OK;
    }

    // }}}
    // {{{ commit()

    function commit()
    {
        return ibase_commit($this->connection);
    }

    // }}}
    // {{{ rollback()

    function rollback($trans_number)
    {
        return ibase_rollback($this->connection, $trans_number);
    }

    // }}}
    // {{{ transactionInit()

    function transactionInit($trans_args = 0)
    {
        return $trans_args ? ibase_trans($trans_args, $this->connection) : ibase_trans();
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
        $sqn = strtoupper($this->getSequenceName($seq_name));
        $repeat = 0;
        do {
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $result =& $this->query("SELECT GEN_ID(${sqn}, 1) "
                                   . 'FROM RDB$GENERATORS '
                                   . "WHERE RDB\$GENERATOR_NAME='${sqn}'");
            $this->popErrorHandling();
            if ($ondemand && DB::isError($result)) {
                $repeat = 1;
                $result = $this->createSequence($seq_name);
                if (DB::isError($result)) {
                    return $result;
                }
            } else {
                $repeat = 0;
            }
        } while ($repeat);
        if (DB::isError($result)) {
            return $this->raiseError($result);
        }
        $arr = $result->fetchRow(DB_FETCHMODE_ORDERED);
        $result->free();
        return $arr[0];
    }

    // }}}
    // {{{ createSequence()

    /**
     * Create the sequence
     *
     * @param string $seq_name the name of the sequence
     * @return mixed DB_OK on success or DB error on error
     * @access public
     */
    function createSequence($seq_name)
    {
        $sqn = strtoupper($this->getSequenceName($seq_name));
        $this->pushErrorHandling(PEAR_ERROR_RETURN);
        $result = $this->query("CREATE GENERATOR ${sqn}");
        $this->popErrorHandling();

        return $result;
    }

    // }}}
    // {{{ dropSequence()

    /**
     * Drop a sequence
     *
     * @param string $seq_name the name of the sequence
     * @return mixed DB_OK on success or DB error on error
     * @access public
     */
    function dropSequence($seq_name)
    {
        $sqn = strtoupper($this->getSequenceName($seq_name));
        return $this->query('DELETE FROM RDB$GENERATORS '
                            . "WHERE RDB\$GENERATOR_NAME='${sqn}'");
    }

    // }}}
    // {{{ _ibaseFieldFlags()

    /**
     * get the Flags of a Field
     *
     * @param string $field_name the name of the field
     * @param string $table_name the name of the table
     *
     * @return string The flags of the field ("primary_key", "unique_key", "not_null"
     *                "default", "computed" and "blob" are supported)
     * @access private
     */
    function _ibaseFieldFlags($field_name, $table_name)
    {
        $sql = 'SELECT R.RDB$CONSTRAINT_TYPE CTYPE'
               .' FROM RDB$INDEX_SEGMENTS I'
               .'  JOIN RDB$RELATION_CONSTRAINTS R ON I.RDB$INDEX_NAME=R.RDB$INDEX_NAME'
               .' WHERE I.RDB$FIELD_NAME=\'' . $field_name . '\''
               .'  AND UPPER(R.RDB$RELATION_NAME)=\'' . strtoupper($table_name) . '\'';

        $result = ibase_query($this->connection, $sql);
        if (!$result) {
            return $this->ibaseRaiseError();
        }

        $flags = '';
        if ($obj = @ibase_fetch_object($result)) {
            ibase_free_result($result);
            if (isset($obj->CTYPE)  && trim($obj->CTYPE) == 'PRIMARY KEY') {
                $flags .= 'primary_key ';
            }
            if (isset($obj->CTYPE)  && trim($obj->CTYPE) == 'UNIQUE') {
                $flags .= 'unique_key ';
            }
        }

        $sql = 'SELECT R.RDB$NULL_FLAG AS NFLAG,'
               .'  R.RDB$DEFAULT_SOURCE AS DSOURCE,'
               .'  F.RDB$FIELD_TYPE AS FTYPE,'
               .'  F.RDB$COMPUTED_SOURCE AS CSOURCE'
               .' FROM RDB$RELATION_FIELDS R '
               .'  JOIN RDB$FIELDS F ON R.RDB$FIELD_SOURCE=F.RDB$FIELD_NAME'
               .' WHERE UPPER(R.RDB$RELATION_NAME)=\'' . strtoupper($table_name) . '\''
               .'  AND R.RDB$FIELD_NAME=\'' . $field_name . '\'';

        $result = ibase_query($this->connection, $sql);
        if (!$result) {
            return $this->ibaseRaiseError();
        }
        if ($obj = @ibase_fetch_object($result)) {
            ibase_free_result($result);
            if (isset($obj->NFLAG)) {
                $flags .= 'not_null ';
            }
            if (isset($obj->DSOURCE)) {
                $flags .= 'default ';
            }
            if (isset($obj->CSOURCE)) {
                $flags .= 'computed ';
            }
            if (isset($obj->FTYPE)  && $obj->FTYPE == 261) {
                $flags .= 'blob ';
            }
        }

        return trim($flags);
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set.
     *
     * NOTE: only supports 'table' and 'flags' if <var>$result</var>
     * is a table name.
     *
     * @param object|string  $result  DB_result object from a query or a
     *                                string containing the name of a table
     * @param int            $mode    a valid tableInfo mode
     * @return array  an associative array with the information requested
     *                or an error object if something is wrong
     * @access public
     * @internal
     * @see DB_common::tableInfo()
     */
    function tableInfo($result, $mode = null)
    {
        if (isset($result->result)) {
            /*
             * Probably received a result object.
             * Extract the result resource identifier.
             */
            $id = $result->result;
            $got_string = false;
        } elseif (is_string($result)) {
            /*
             * Probably received a table name.
             * Create a result resource identifier.
             */
             $id = @ibase_query($this->connection,
                                "SELECT * FROM $result WHERE 1=0");
            $got_string = true;
        } else {
            /*
             * Probably received a result resource identifier.
             * Copy it.
             * Depricated.  Here for compatibility only.
             */
             $id = $result;
            $got_string = false;
        }

        if (!is_resource($id)) {
            return $this->ibaseRaiseError(DB_ERROR_NEED_MORE_DATA);
        }

        if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE) {
            $case_func = 'strtolower';
        } else {
            $case_func = 'strval';
        }

        $count = @ibase_num_fields($id);

        // made this IF due to performance (one if is faster than $count if's)
        if (!$mode) {
            for ($i=0; $i<$count; $i++) {
                $info = @ibase_field_info($id, $i);
                $res[$i]['table'] = $got_string ? $case_func($result) : '';
                $res[$i]['name']  = $case_func($info['name']);
                $res[$i]['type']  = $info['type'];
                $res[$i]['len']   = $info['length'];
                $res[$i]['flags'] = ($got_string) ? $this->_ibaseFieldFlags($info['name'], $result) : '';
            }
        } else { // full
            $res['num_fields']= $count;

            for ($i=0; $i<$count; $i++) {
                $info = @ibase_field_info($id, $i);
                $res[$i]['table'] = $got_string ? $case_func($result) : '';
                $res[$i]['name']  = $case_func($info['name']);
                $res[$i]['type']  = $info['type'];
                $res[$i]['len']   = $info['length'];
                $res[$i]['flags'] = ($got_string) ? $this->_ibaseFieldFlags($info['name'], $result) : '';

                if ($mode & DB_TABLEINFO_ORDER) {
                    $res['order'][$res[$i]['name']] = $i;
                }
                if ($mode & DB_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                }
            }
        }

        // free the result only if we were called on a table
        if ($got_string) {
            ibase_free_result($id);
        }
        return $res;
    }

    // }}}
    // {{{ ibaseRaiseError()

    /**
     * Gather information about an error, then use that info to create a
     * DB error object and finally return that object.
     *
     * @param  integer  $db_errno  PEAR error number (usually a DB constant) if
     *                             manually raising an error
     * @param  string  $native_errmsg  text of error message if known
     * @return object  DB error object
     * @see DB_common::errorCode()
     * @see DB_common::raiseError()
     */
    function &ibaseRaiseError($db_errno = null, $native_errmsg = null)
    {
        if ($native_errmsg === null) {
            $native_errmsg = ibase_errmsg();
        }
        // memo for the interbase php module hackers: we need something similar
        // to mysql_errno() to retrieve error codes instead of this ugly hack
        if (preg_match('/^([^0-9\-]+)([0-9\-]+)\s+(.*)$/', $native_errmsg, $m)) {
            $native_errno = (int)$m[2];
        } else {
            $native_errno = null;
        }
        // try to map the native error to the DB one
        if ($db_errno === null) {
            if ($native_errno) {
                // try to interpret Interbase error code (that's why we need ibase_errno()
                // in the interbase module to return the real error code)
                switch ($native_errno) {
                    case -204:
                        if (is_int(strpos($m[3], 'Table unknown'))) {
                            $db_errno = DB_ERROR_NOSUCHTABLE;
                        }
                        break;
                    default:
                        $db_errno = $this->errorCode($native_errno);
                }
            } else {
                $error_regexps = array(
                    '/[tT]able not found/' => DB_ERROR_NOSUCHTABLE,
                    '/[tT]able .* already exists/' => DB_ERROR_ALREADY_EXISTS,
                    '/violation of [\w ]+ constraint/' => DB_ERROR_CONSTRAINT,
                    '/conversion error from string/' => DB_ERROR_INVALID_NUMBER,
                    '/no permission for/' => DB_ERROR_ACCESS_VIOLATION,
                    '/arithmetic exception, numeric overflow, or string truncation/' => DB_ERROR_DIVZERO
                );
                foreach ($error_regexps as $regexp => $code) {
                    if (preg_match($regexp, $native_errmsg)) {
                        $db_errno = $code;
                        $native_errno = null;
                        break;
                    }
                }
            }
        }
        $tmp =& $this->raiseError($db_errno, null, null, null, $native_errmsg);
        return $tmp;
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
