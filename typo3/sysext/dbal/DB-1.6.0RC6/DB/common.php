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
// | Author: Stig Bakken <ssb@php.net>                                    |
// |         Tomas V.V.Cox <cox@idecnet.com>                              |
// | Maintainer: Daniel Convissor <danielc@php.net>                       |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'PEAR.php';

/**
 * DB_common is a base class for DB implementations, and must be
 * inherited by all such
 *
 * @package  DB
 * @version  $Id$
 * @category Database
 * @author   Stig Bakken <ssb@php.net>
 * @author   Tomas V.V.Cox <cox@idecnet.com>
 */
class DB_common extends PEAR
{
    // {{{ properties

    /**
     * assoc of capabilities for this DB implementation
     * $features['limit'] =>  'emulate' => emulate with fetch row by number
     *                        'alter'   => alter the query
     *                        false     => skip rows
     * @var array
     */
    var $features = array();

    /**
     * assoc mapping native error codes to DB ones
     * @var array
     */
    var $errorcode_map = array();

    /**
     * DB type (mysql, oci8, odbc etc.)
     * @var string
     */
    var $phptype;

    /**
     * @var string
     */
    var $prepare_tokens;

    /**
     * @var string
     */
    var $prepare_types;

    /**
     * @var string
     */
    var $prepared_queries;

    /**
     * @var integer
     */
    var $prepare_maxstmt = 0;

    /**
     * @var string
     */
    var $last_query = '';

    /**
     * @var integer
     */
    var $fetchmode = DB_FETCHMODE_ORDERED;

    /**
     * @var string
     */
    var $fetchmode_object_class = 'stdClass';

    /**
     * Run-time configuration options.
     *
     * The 'optimize' option has been deprecated.  Use the 'portability'
     * option instead.
     *
     * @see DB_common::setOption()
     * @var array
     */
    var $options = array(
        'persistent' => false,
        'ssl' => false,
        'debug' => 0,
        'seqname_format' => '%s_seq',
        'autofree' => false,
        'portability' => DB_PORTABILITY_NONE,
        'optimize' => 'performance',  // Deprecated.  Use 'portability'.
    );

    /**
     * DB handle
     * @var resource
     */
    var $dbh;

    // }}}
    // {{{ toString()

    /**
     * String conversation
     *
     * @return string
     * @access private
     */
    function toString()
    {
        $info = strtolower(get_class($this));
        $info .=  ': (phptype=' . $this->phptype .
                  ', dbsyntax=' . $this->dbsyntax .
                  ')';

        if ($this->connection) {
            $info .= ' [connected]';
        }

        return $info;
    }

    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function DB_common()
    {
        $this->PEAR('DB_Error');
    }

    // }}}
    // {{{ quoteString()

    /**
     * DEPRECATED: Quotes a string so it can be safely used within string
     * delimiters in a query
     *
     * @return string quoted string
     *
     * @see DB_common::quoteSmart(), DB_common::escapeSimple()
     * @deprecated  Deprecated in release 1.2 or lower
     * @internal
     */
    function quoteString($string)
    {
        $string = $this->quote($string);
        if ($string{0} == "'") {
            return substr($string, 1, -1);
        }
        return $string;
    }

    // }}}
    // {{{ quote()

    /**
     * DEPRECATED: Quotes a string so it can be safely used in a query
     *
     * @param string $string the input string to quote
     *
     * @return string The NULL string or the string quotes
     *                in magic_quote_sybase style
     *
     * @see DB_common::quoteSmart(), DB_common::escapeSimple()
     * @deprecated  Deprecated in release 1.6.0
     * @internal
     */
    function quote($string = null)
    {
        return ($string === null) ? 'NULL' : "'".str_replace("'", "''", $string)."'";
    }

    // }}}
    // {{{ quoteIdentifier()

    /**
     * Quote a string so it can be safely used as a table or column name
     *
     * Delimiting style depends on which database driver is being used.
     *
     * NOTE: just because you CAN use delimited identifiers doesn't mean
     * you SHOULD use them.  In general, they end up causing way more
     * problems than they solve.
     *
     * Portability is broken by using the following characters inside
     * delimited identifiers:
     *   + backtick (<kbd>`</kbd>) -- due to MySQL
     *   + double quote (<kbd>"</kbd>) -- due to Oracle
     *   + brackets (<kbd>[</kbd> or <kbd>]</kbd>) -- due to Access
     *
     * Delimited identifiers are known to generally work correctly under
     * the following drivers:
     *   + mssql
     *   + mysql
     *   + mysql4
     *   + oci8
     *   + odbc(access)
     *   + odbc(db2)
     *   + pgsql
     *   + sqlite
     *   + sybase
     *
     * InterBase doesn't seem to be able to use delimited identifiers
     * via PHP 4.  They work fine under PHP 5.
     *
     * @param string $str  identifier name to be quoted
     *
     * @return string  quoted identifier string
     *
     * @since 1.6.0
     * @access public
     */
    function quoteIdentifier($str)
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }

    // }}}
    // {{{ quoteSmart()

    /**
     * Format input so it can be safely used in a query
     *
     * The output depends on the PHP data type of input and the database
     * type being used.
     *
     * @param mixed $in  data to be quoted
     *
     * @return mixed  the format of the results depends on the input's
     *                PHP type:
     *
     * <ul>
     *  <li>
     *    <kbd>input</kbd> -> <samp>returns</samp>
     *  </li>
     *  <li>
     *    <kbd>null</kbd> -> the string <samp>NULL</samp>
     *  </li>
     *  <li>
     *    <kbd>integer</kbd> or <kbd>double</kbd> -> the unquoted number
     *  </li>
     *  <li>
     *    &type.bool; -> output depends on the driver in use
     *    Most drivers return integers: <samp>1</samp> if
     *    <kbd>true</kbd> or <samp>0</samp> if 
     *    <kbd>false</kbd>.
     *    Some return strings: <samp>TRUE</samp> if
     *    <kbd>true</kbd> or <samp>FALSE</samp> if 
     *    <kbd>false</kbd>.
     *    Finally one returns strings: <samp>T</samp> if
     *    <kbd>true</kbd> or <samp>F</samp> if 
     *    <kbd>false</kbd>. Here is a list of each DBMS,
     *    the values returned and the suggested column type:
     *    <ul>
     *      <li>
     *        <kbd>dbase</kbd> -> <samp>T/F</samp>
     *        (<kbd>Logical</kbd>)
     *      </li>
     *      <li>
     *        <kbd>fbase</kbd> -> <samp>TRUE/FALSE</samp>
     *        (<kbd>BOOLEAN</kbd>)
     *      </li>
     *      <li>
     *        <kbd>ibase</kbd> -> <samp>1/0</samp>
     *        (<kbd>SMALLINT</kbd>) [1]
     *      </li>
     *      <li>
     *        <kbd>ifx</kbd> -> <samp>1/0</samp>
     *        (<kbd>SMALLINT</kbd>) [1]
     *      </li>
     *      <li>
     *        <kbd>msql</kbd> -> <samp>1/0</samp>
     *        (<kbd>INTEGER</kbd>)
     *      </li>
     *      <li>
     *        <kbd>mssql</kbd> -> <samp>1/0</samp>
     *        (<kbd>BIT</kbd>)
     *      </li>
     *      <li>
     *        <kbd>mysql</kbd> -> <samp>1/0</samp>
     *        (<kbd>TINYINT(1)</kbd>)
     *      </li>
     *      <li>
     *        <kbd>mysql4</kbd> -> <samp>1/0</samp>
     *        (<kbd>TINYINT(1)</kbd>)
     *      </li>
     *      <li>
     *        <kbd>oci8</kbd> -> <samp>1/0</samp>
     *        (<kbd>NUMBER(1)</kbd>)
     *      </li>
     *      <li>
     *        <kbd>odbc</kbd> -> <samp>1/0</samp>
     *        (<kbd>SMALLINT</kbd>) [1]
     *      </li>
     *      <li>
     *        <kbd>pgsql</kbd> -> <samp>TRUE/FALSE</samp>
     *        (<kbd>BOOLEAN</kbd>)
     *      </li>
     *      <li>
     *        <kbd>sqlite</kbd> -> <samp>1/0</samp>
     *        (<kbd>INTEGER</kbd>)
     *      </li>
     *      <li>
     *        <kbd>sybase</kbd> -> <samp>1/0</samp>
     *        (<kbd>TINYINT(1)</kbd>)
     *      </li>
     *    </ul>
     *    [1] Accommodate the lowest common denominator because not all
     *    versions of have <kbd>BOOLEAN</kbd>.
     *  </li>
     *  <li>
     *    other (including strings and numeric strings) ->
     *    the data with single quotes escaped by preceeding
     *    single quotes, backslashes are escaped by preceeding
     *    backslashes, then the whole string is encapsulated
     *    between single quotes
     *  </li>
     * </ul>
     *      
     * @since 1.6.0
     * @see DB_common::escapeSimple()
     * @access public
     */
    function quoteSmart($in)
    {
        if (is_int($in) || is_double($in)) {
            return $in;
        } elseif (is_bool($in)) {
            return $in ? 1 : 0;
        } elseif (is_null($in)) {
            return 'NULL';
        } else {
            return "'" . $this->escapeSimple($in) . "'";
        }
    }

    // }}}
    // {{{ escapeSimple()

    /**
     * Escape a string according to the current DBMS's standards
     *
     * @param string $str  the string to be escaped
     *
     * @return string  the escaped string
     *
     * @since 1.6.0
     * @see DB_common::quoteSmart()
     * @access public
     */
    function escapeSimple($str) {
        return str_replace("'", "''", $str);
    }

    // }}}
    // {{{ provides()

    /**
     * Tell whether a DB implementation or its backend extension
     * supports a given feature
     *
     * @param array $feature name of the feature (see the DB class doc)
     * @return bool whether this DB implementation supports $feature
     * @access public
     */
    function provides($feature)
    {
        return $this->features[$feature];
    }

    // }}}
    // {{{ errorCode()

    /**
     * Map native error codes to DB's portable ones
     *
     * Requires that the DB implementation's constructor fills
     * in the <var>$errorcode_map</var> property.
     *
     * @param mixed  $nativecode  the native error code, as returned by the
     * backend database extension (string or integer)
     *
     * @return int a portable DB error code, or DB_ERROR if this DB
     * implementation has no mapping for the given error code.
     *
     * @access public
     */
    function errorCode($nativecode)
    {
        if (isset($this->errorcode_map[$nativecode])) {
            return $this->errorcode_map[$nativecode];
        }
        // Fall back to DB_ERROR if there was no mapping.
        return DB_ERROR;
    }

    // }}}
    // {{{ errorMessage()

    /**
     * Map a DB error code to a textual message.  This is actually
     * just a wrapper for DB::errorMessage()
     *
     * @param integer $dbcode the DB error code
     *
     * @return string the corresponding error message, of FALSE
     * if the error code was unknown
     *
     * @access public
     */
    function errorMessage($dbcode)
    {
        return DB::errorMessage($this->errorcode_map[$dbcode]);
    }

    // }}}
    // {{{ raiseError()

    /**
     * Communicate an error and invoke error callbacks, etc
     *
     * Basically a wrapper for PEAR::raiseError without the message string.
     *
     * @param mixed    integer error code, or a PEAR error object (all
     *                 other parameters are ignored if this parameter is
     *                 an object
     *
     * @param int      error mode, see PEAR_Error docs
     *
     * @param mixed    If error mode is PEAR_ERROR_TRIGGER, this is the
     *                 error level (E_USER_NOTICE etc).  If error mode is
     *                 PEAR_ERROR_CALLBACK, this is the callback function,
     *                 either as a function name, or as an array of an
     *                 object and method name.  For other error modes this
     *                 parameter is ignored.
     *
     * @param string   Extra debug information.  Defaults to the last
     *                 query and native error code.
     *
     * @param mixed    Native error code, integer or string depending the
     *                 backend.
     *
     * @return object  a PEAR error object
     *
     * @access public
     * @see PEAR_Error
     */
    function &raiseError($code = DB_ERROR, $mode = null, $options = null,
                         $userinfo = null, $nativecode = null)
    {
        // The error is yet a DB error object
        if (is_object($code)) {
            // because we the static PEAR::raiseError, our global
            // handler should be used if it is set
            if ($mode === null && !empty($this->_default_error_mode)) {
                $mode    = $this->_default_error_mode;
                $options = $this->_default_error_options;
            }
            $tmp = PEAR::raiseError($code, null, $mode, $options, null, null, true);
            return $tmp;
        }

        if ($userinfo === null) {
            $userinfo = $this->last_query;
        }

        if ($nativecode) {
            $userinfo .= ' [nativecode=' . trim($nativecode) . ']';
        }

        $tmp = PEAR::raiseError(null, $code, $mode, $options, $userinfo,
                                'DB_Error', true);
        return $tmp;
    }

    // }}}
    // {{{ setFetchMode()

    /**
     * Sets which fetch mode should be used by default on queries
     * on this connection
     *
     * @param integer $fetchmode DB_FETCHMODE_ORDERED or
     *        DB_FETCHMODE_ASSOC, possibly bit-wise OR'ed with
     *        DB_FETCHMODE_FLIPPED.
     *
     * @param string $object_class The class of the object
     *                      to be returned by the fetch methods when
     *                      the DB_FETCHMODE_OBJECT mode is selected.
     *                      If no class is specified by default a cast
     *                      to object from the assoc array row will be done.
     *                      There is also the posibility to use and extend the
     *                      'DB_Row' class.
     *
     * @see DB_FETCHMODE_ORDERED
     * @see DB_FETCHMODE_ASSOC
     * @see DB_FETCHMODE_FLIPPED
     * @see DB_FETCHMODE_OBJECT
     * @see DB_Row::DB_Row()
     * @access public
     */
    function setFetchMode($fetchmode, $object_class = null)
    {
        switch ($fetchmode) {
            case DB_FETCHMODE_OBJECT:
                if ($object_class) {
                    $this->fetchmode_object_class = $object_class;
                }
            case DB_FETCHMODE_ORDERED:
            case DB_FETCHMODE_ASSOC:
                $this->fetchmode = $fetchmode;
                break;
            default:
                return $this->raiseError('invalid fetchmode mode');
        }
    }

    // }}}
    // {{{ setOption()

    /**
     * Set run-time configuration options for PEAR DB
     *
     * Options, their data types, default values and description:
     * <ul>
     * <li>
     * <var>autofree</var> <kbd>boolean</kbd> = <samp>false</samp>
     *      <br />should results be freed automatically when there are no
     *            more rows?
     * </li><li>
     * <var>debug</var> <kbd>integer</kbd> = <samp>0</samp>
     *      <br />debug level
     * </li><li>
     * <var>persistent</var> <kbd>boolean</kbd> = <samp>false</samp>
     *      <br />should the connection be persistent?
     * </li><li>
     * <var>portability</var> <kbd>integer</kbd> = <samp>DB_PORTABILITY_NONE</samp>
     *      <br />portability mode constant (see below)
     * </li><li>
     * <var>seqname_format</var> <kbd>string</kbd> = <samp>%s_seq</samp>
     *      <br />the sprintf() format string used on sequence names.  This
     *            format is applied to sequence names passed to
     *            createSequence(), nextID() and dropSequence().
     * </li><li>
     * <var>ssl</var> <kbd>boolean</kbd> = <samp>false</samp>
     *      <br />use ssl to connect?
     * </li>
     * </ul>
     *
     * -----------------------------------------
     *
     * PORTABILITY MODES
     *
     * These modes are bitwised, so they can be combined using <kbd>|</kbd>
     * and removed using <kbd>^</kbd>.  See the examples section below on how
     * to do this.
     *
     * <samp>DB_PORTABILITY_NONE</samp>
     * turn off all portability features
     *
     * This mode gets automatically turned on if the deprecated
     * <var>optimize</var> option gets set to <samp>performance</samp>.
     *
     *
     * <samp>DB_PORTABILITY_LOWERCASE</samp>
     * convert names of tables and fields to lower case when using
     * <kbd>get*()</kbd>, <kbd>fetch*()</kbd> and <kbd>tableInfo()</kbd>
     *
     * This mode gets automatically turned on in the following databases
     * if the deprecated option <var>optimize</var> gets set to
     * <samp>portability</samp>:
     * + oci8
     *
     *
     * <samp>DB_PORTABILITY_RTRIM</samp>
     * right trim the data output by <kbd>get*()</kbd> <kbd>fetch*()</kbd>
     *
     *
     * <samp>DB_PORTABILITY_DELETE_COUNT</samp>
     * force reporting the number of rows deleted
     *
     * Some DBMS's don't count the number of rows deleted when performing
     * simple <kbd>DELETE FROM tablename</kbd> queries.  This portability
     * mode tricks such DBMS's into telling the count by adding
     * <samp>WHERE 1=1</samp> to the end of <kbd>DELETE</kbd> queries.
     *
     * This mode gets automatically turned on in the following databases
     * if the deprecated option <var>optimize</var> gets set to
     * <samp>portability</samp>:
     * + fbsql
     * + mysql
     * + mysql4
     * + sqlite
     *
     *
     * <samp>DB_PORTABILITY_NUMROWS</samp>
     * enable hack that makes <kbd>numRows()</kbd> work in Oracle
     *
     * This mode gets automatically turned on in the following databases
     * if the deprecated option <var>optimize</var> gets set to
     * <samp>portability</samp>:
     * + oci8
     *
     *
     * <samp>DB_PORTABILITY_ERRORS</samp>
     * makes certain error messages in certain drivers compatible
     * with those from other DBMS's
     *
     * + mysql, mysql4:  change unique/primary key constraints
     *   DB_ERROR_ALREADY_EXISTS -> DB_ERROR_CONSTRAINT
     *
     * + odbc(access):  MS's ODBC driver reports 'no such field' as code
     *   07001, which means 'too few parameters.'  When this option is on
     *   that code gets mapped to DB_ERROR_NOSUCHFIELD.
     *   DB_ERROR_MISMATCH -> DB_ERROR_NOSUCHFIELD
     *
     *
     * <samp>DB_PORTABILITY_ALL</samp>
     * turn on all portability features
     *
     * -----------------------------------------
     *
     * Example 1. Simple setOption() example
     * <code> <?php
     * $dbh->setOption('autofree', true);
     * ?></code>
     *
     * Example 2. Portability for lowercasing and trimming
     * <code> <?php
     * $dbh->setOption('portability',
     *                  DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_RTRIM);
     * ?></code>
     *
     * Example 3. All portability options except trimming
     * <code> <?php
     * $dbh->setOption('portability',
     *                  DB_PORTABILITY_ALL ^ DB_PORTABILITY_RTRIM);
     * ?></code>
     *
     * @param string $option option name
     * @param mixed  $value value for the option
     *
     * @return int  DB_OK on success.  DB_Error object on failure.
     *
     * @see DB_common::$options
     */
    function setOption($option, $value)
    {
        if (isset($this->options[$option])) {
            $this->options[$option] = $value;

            /*
             * Backwards compatibility check for the deprecated 'optimize'
             * option.  Done here in case settings change after connecting.
             */
            if ($option == 'optimize') {
                if ($value == 'portability') {
                    switch ($this->phptype) {
                        case 'oci8':
                            $this->options['portability'] =
                                    DB_PORTABILITY_LOWERCASE |
                                    DB_PORTABILITY_NUMROWS;
                            break;
                        case 'fbsql':
                        case 'mysql':
                        case 'mysql4':
                        case 'sqlite':
                            $this->options['portability'] =
                                    DB_PORTABILITY_DELETE_COUNT;
                            break;
                    }
                } else {
                    $this->options['portability'] = DB_PORTABILITY_NONE;
                }
            }

            return DB_OK;
        }
        return $this->raiseError("unknown option $option");
    }

    // }}}
    // {{{ getOption()

    /**
     * Returns the value of an option
     *
     * @param string $option option name
     *
     * @return mixed the option value
     */
    function getOption($option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }
        return $this->raiseError("unknown option $option");
    }

    // }}}
    // {{{ prepare()

    /**
     * Prepares a query for multiple execution with execute()
     *
     * Creates a query that can be run multiple times.  Each time it is run,
     * the placeholders, if any, will be replaced by the contents of
     * execute()'s $data argument.
     *
     * Three types of placeholders can be used:
     *   + <kbd>?</kbd>  scalar value (i.e. strings, integers).  The system
     *                   will automatically quote and escape the data.
     *   + <kbd>!</kbd>  value is inserted 'as is'
     *   + <kbd>&</kbd>  requires a file name.  The file's contents get
     *                   inserted into the query (i.e. saving binary
     *                   data in a db)
     *
     * Example 1.
     * <code> <?php
     * $sth = $dbh->prepare('INSERT INTO tbl (a, b, c) VALUES (?, !, &)');
     * $data = array(
     *     "John's text",
     *     "'it''s good'",
     *     'filename.txt'
     * );
     * $res = $dbh->execute($sth, $data);
     * ?></code>
     *
     * Use backslashes to escape placeholder characters if you don't want
     * them to be interpreted as placeholders:
     * <pre>
     *    "UPDATE foo SET col=? WHERE col='over \& under'"
     * </pre>
     *
     * With some database backends, this is emulated.
     *
     * {@internal ibase and oci8 have their own prepare() methods.}}
     *
     * @param string $query query to be prepared
     *
     * @return mixed DB statement resource on success. DB_Error on failure.
     *
     * @see DB_common::execute()
     * @access public
     */
    function prepare($query)
    {
        $tokens   = preg_split('/((?<!\\\)[&?!])/', $query, -1,
                               PREG_SPLIT_DELIM_CAPTURE);
        $token     = 0;
        $types     = array();
        $newtokens = array();

        foreach ($tokens as $val) {
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
                    $newtokens[] = preg_replace('/\\\([&?!])/', "\\1", $val);
            }
        }

        $this->prepare_tokens[] = &$newtokens;
        end($this->prepare_tokens);

        $k = key($this->prepare_tokens);
        $this->prepare_types[$k] = $types;
        $this->prepared_queries[$k] = implode(' ', $newtokens);

        return $k;
    }

    // }}}
    // {{{ autoPrepare()

    /**
     * Automaticaly generate an insert or update query and pass it to prepare()
     *
     * @param string $table name of the table
     * @param array $table_fields ordered array containing the fields names
     * @param int $mode type of query to make (DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE)
     * @param string $where in case of update queries, this string will be put after the sql WHERE statement
     * @return resource handle for the query
     * @see DB_common::prepare(), DB_common::buildManipSQL()
     * @access public
     */
    function autoPrepare($table, $table_fields, $mode = DB_AUTOQUERY_INSERT, $where = false)
    {
        $query = $this->buildManipSQL($table, $table_fields, $mode, $where);
        return $this->prepare($query);
    }

    // }}}
    // {{{ autoExecute()

    /**
     * Automaticaly generate an insert or update query and call prepare()
     * and execute() with it
     *
     * @param string $table name of the table
     * @param array $fields_values assoc ($key=>$value) where $key is a field name and $value its value
     * @param int $mode type of query to make (DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE)
     * @param string $where in case of update queries, this string will be put after the sql WHERE statement
     * @return mixed  a new DB_Result or a DB_Error when fail
     * @see DB_common::autoPrepare(), DB_common::buildManipSQL()
     * @access public
     */
    function autoExecute($table, $fields_values, $mode = DB_AUTOQUERY_INSERT, $where = false)
    {
        $sth = $this->autoPrepare($table, array_keys($fields_values), $mode, $where);
        $ret =& $this->execute($sth, array_values($fields_values));
        $this->freePrepared($sth);
        return $ret;

    }

    // }}}
    // {{{ buildManipSQL()

    /**
     * Make automaticaly an sql query for prepare()
     *
     * Example : buildManipSQL('table_sql', array('field1', 'field2', 'field3'), DB_AUTOQUERY_INSERT)
     *           will return the string : INSERT INTO table_sql (field1,field2,field3) VALUES (?,?,?)
     * NB : - This belongs more to a SQL Builder class, but this is a simple facility
     *      - Be carefull ! If you don't give a $where param with an UPDATE query, all
     *        the records of the table will be updated !
     *
     * @param string $table name of the table
     * @param array $table_fields ordered array containing the fields names
     * @param int $mode type of query to make (DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE)
     * @param string $where in case of update queries, this string will be put after the sql WHERE statement
     * @return string sql query for prepare()
     * @access public
     */
    function buildManipSQL($table, $table_fields, $mode, $where = false)
    {
        if (count($table_fields) == 0) {
            $this->raiseError(DB_ERROR_NEED_MORE_DATA);
        }
        $first = true;
        switch ($mode) {
            case DB_AUTOQUERY_INSERT:
                $values = '';
                $names = '';
                foreach ($table_fields as $value) {
                    if ($first) {
                        $first = false;
                    } else {
                        $names .= ',';
                        $values .= ',';
                    }
                    $names .= $value;
                    $values .= '?';
                }
                return "INSERT INTO $table ($names) VALUES ($values)";
            case DB_AUTOQUERY_UPDATE:
                $set = '';
                foreach ($table_fields as $value) {
                    if ($first) {
                        $first = false;
                    } else {
                        $set .= ',';
                    }
                    $set .= "$value = ?";
                }
                $sql = "UPDATE $table SET $set";
                if ($where) {
                    $sql .= " WHERE $where";
                }
                return $sql;
            default:
                $this->raiseError(DB_ERROR_SYNTAX);
        }
    }

    // }}}
    // {{{ execute()

    /**
     * Executes a DB statement prepared with prepare()
     *
     * Example 1.
     * <code> <?php
     * $sth = $dbh->prepare('INSERT INTO tbl (a, b, c) VALUES (?, !, &)');
     * $data = array(
     *     "John's text",
     *     "'it''s good'",
     *     'filename.txt'
     * );
     * $res = $dbh->execute($sth, $data);
     * ?></code>
     *
     * @param resource  $stmt  a DB statement resource returned from prepare()
     * @param mixed  $data  array, string or numeric data to be used in
     *                      execution of the statement.  Quantity of items
     *                      passed must match quantity of placeholders in
     *                      query:  meaning 1 placeholder for non-array
     *                      parameters or 1 placeholder per array element.
     *
     * @return mixed  a new DB_Result or a DB_Error when fail
     *
     * {@internal ibase and oci8 have their own execute() methods.}}
     *
     * @see DB_common::prepare()
     * @access public
     */
    function &execute($stmt, $data = array())
    {
        $realquery = $this->executeEmulateQuery($stmt, $data);
        if (DB::isError($realquery)) {
            return $realquery;
        }
        $result = $this->simpleQuery($realquery);

        if (DB::isError($result) || $result === DB_OK) {
            return $result;
        } else {
            $tmp =& new DB_result($this, $result);
            return $tmp;
        }
    }

    // }}}
    // {{{ executeEmulateQuery()

    /**
     * Emulates the execute statement, when not supported
     *
     * @param resource  $stmt  a DB statement resource returned from execute()
     * @param mixed  $data  array, string or numeric data to be used in
     *                      execution of the statement.  Quantity of items
     *                      passed must match quantity of placeholders in
     *                      query:  meaning 1 placeholder for non-array
     *                      parameters or 1 placeholder per array element.
     *
     * @return mixed a string containing the real query run when emulating
     *               prepare/execute.  A DB error code is returned on failure.
     *
     * @see DB_common::execute()
     * @access private
     */
    function executeEmulateQuery($stmt, $data = array())
    {
        if (!is_array($data)) {
            $data = array($data);
        }

        if (count($this->prepare_types[$stmt]) != count($data)) {
            $this->last_query = $this->prepared_queries[$stmt];
            return $this->raiseError(DB_ERROR_MISMATCH);
        }

        $realquery = $this->prepare_tokens[$stmt][0];

        $i = 0;
        foreach ($data as $value) {
            if ($this->prepare_types[$stmt][$i] == DB_PARAM_SCALAR) {
                $realquery .= $this->quoteSmart($value);
            } elseif ($this->prepare_types[$stmt][$i] == DB_PARAM_OPAQUE) {
                $fp = @fopen($value, 'rb');
                if (!$fp) {
                    return $this->raiseError(DB_ERROR_ACCESS_VIOLATION);
                }
                $realquery .= $this->quoteSmart(fread($fp, filesize($value)));
                fclose($fp);
            } else {
                $realquery .= $value;
            }

            $realquery .= $this->prepare_tokens[$stmt][++$i];
        }

        return $realquery;
    }

    // }}}
    // {{{ executeMultiple()

    /**
     * This function does several execute() calls on the same
     * statement handle
     *
     * $data must be an array indexed numerically
     * from 0, one execute call is done for every "row" in the array.
     *
     * If an error occurs during execute(), executeMultiple() does not
     * execute the unfinished rows, but rather returns that error.
     *
     * @param resource $stmt query handle from prepare()
     * @param array    $data numeric array containing the
     *                       data to insert into the query
     *
     * @return mixed DB_OK or DB_Error
     *
     * @see DB_common::prepare(), DB_common::execute()
     * @access public
     */
    function executeMultiple($stmt, $data)
    {
        foreach ($data as $value) {
            $res =& $this->execute($stmt, $value);
            if (DB::isError($res)) {
                return $res;
            }
        }
        return DB_OK;
    }

    // }}}
    // {{{ freePrepared()

    /**
     * Free the resource used in a prepared query
     *
     * @param $stmt The resurce returned by the prepare() function
     * @see DB_common::prepare()
     */
    function freePrepared($stmt)
    {
        // Free the internal prepared vars
        if (isset($this->prepare_tokens[$stmt])) {
            unset($this->prepare_tokens[$stmt]);
            unset($this->prepare_types[$stmt]);
            unset($this->prepared_queries[$stmt]);
            return true;
        }
        return false;
    }

    // }}}
    // {{{ modifyQuery()

    /**
     * This method is used by backends to alter queries for various
     * reasons
     *
     * It is defined here to assure that all implementations
     * have this method defined.
     *
     * @param string $query  query to modify
     *
     * @return the new (modified) query
     *
     * @access private
     */
    function modifyQuery($query) {
        return $query;
    }

    // }}}
    // {{{ modifyLimitQuery()

    /**
     * This method is used by backends to alter limited queries
     *
     * @param string  $query query to modify
     * @param integer $from  the row to start to fetching
     * @param integer $count the numbers of rows to fetch
     *
     * @return the new (modified) query
     *
     * @access private
     */
    function modifyLimitQuery($query, $from, $count)
    {
        return $query;
    }

    // }}}
    // {{{ query()

    /**
     * Send a query to the database and return any results with a
     * DB_result object
     *
     * The query string can be either a normal statement to be sent directly
     * to the server OR if <var>$params</var> are passed the query can have
     * placeholders and it will be passed through prepare() and execute().
     *
     * @param string $query  the SQL query or the statement to prepare
     * @param mixed  $params array, string or numeric data to be used in
     *                       execution of the statement.  Quantity of items
     *                       passed must match quantity of placeholders in
     *                       query:  meaning 1 placeholder for non-array
     *                       parameters or 1 placeholder per array element.
     *
     * @return mixed  a DB_result object or DB_OK on success, a DB
     *                error on failure
     *
     * @see DB_result, DB_common::prepare(), DB_common::execute()
     * @access public
     */
    function &query($query, $params = array())
    {
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);
            if (DB::isError($sth)) {
                return $sth;
            }
            $ret =& $this->execute($sth, $params);
            $this->freePrepared($sth);
            return $ret;
        } else {
            $result = $this->simpleQuery($query);
            if (DB::isError($result) || $result === DB_OK) {
                return $result;
            } else {
                $tmp =& new DB_result($this, $result);
                return $tmp;
            }
        }
    }

    // }}}
    // {{{ limitQuery()

    /**
     * Generates a limited query
     *
     * @param string  $query query
     * @param integer $from  the row to start to fetching
     * @param integer $count the numbers of rows to fetch
     * @param array   $params required for a statement
     *
     * @return mixed a DB_Result object, DB_OK or a DB_Error
     *
     * @access public
     */
    function &limitQuery($query, $from, $count, $params = array())
    {
        $query = $this->modifyLimitQuery($query, $from, $count);
        if (DB::isError($query)){
            return $query;
        }
        $result =& $this->query($query, $params);
        if (is_a($result, 'DB_result')) {
            $result->setOption('limit_from', $from);
            $result->setOption('limit_count', $count);
        }
        return $result;
    }

    // }}}
    // {{{ getOne()

    /**
     * Fetch the first column of the first row of data returned from
     * a query
     *
     * Takes care of doing the query and freeing the results when finished.
     *
     * @param string $query  the SQL query
     * @param mixed  $params array, string or numeric data to be used in
     *                       execution of the statement.  Quantity of items
     *                       passed must match quantity of placeholders in
     *                       query:  meaning 1 placeholder for non-array
     *                       parameters or 1 placeholder per array element.
     *
     * @return mixed  the returned value of the query.  DB_Error on failure.
     *
     * @access public
     */
    function &getOne($query, $params = array())
    {
        settype($params, 'array');
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);
            if (DB::isError($sth)) {
                return $sth;
            }
            $res =& $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res =& $this->query($query);
        }

        if (DB::isError($res)) {
            return $res;
        }

        $err = $res->fetchInto($row, DB_FETCHMODE_ORDERED);
        $res->free();

        if ($err !== DB_OK) {
            return $err;
        }

        return $row[0];
    }

    // }}}
    // {{{ getRow()

    /**
     * Fetch the first row of data returned from a query
     *
     * Takes care of doing the query and freeing the results when finished.
     *
     * @param string $query  the SQL query
     * @param array  $params array to be used in execution of the statement.
     *                       Quantity of array elements must match quantity
     *                       of placeholders in query.  This function does
     *                       NOT support scalars.
     * @param int    $fetchmode  the fetch mode to use
     *
     * @return array the first row of results as an array indexed from
     *               0, or a DB error code.
     *
     * @access public
     */
    function &getRow($query,
                     $params = array(),
                     $fetchmode = DB_FETCHMODE_DEFAULT)
    {
        // compat check, the params and fetchmode parameters used to
        // have the opposite order
        if (!is_array($params)) {
            if (is_array($fetchmode)) {
                if ($params === null) {
                    $tmp = DB_FETCHMODE_DEFAULT;
                } else {
                    $tmp = $params;
                }
                $params = $fetchmode;
                $fetchmode = $tmp;
            } elseif ($params !== null) {
                $fetchmode = $params;
                $params = array();
            }
        }

        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);
            if (DB::isError($sth)) {
                return $sth;
            }
            $res =& $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res =& $this->query($query);
        }

        if (DB::isError($res)) {
            return $res;
        }

        $err = $res->fetchInto($row, $fetchmode);

        $res->free();

        if ($err !== DB_OK) {
            return $err;
        }

        return $row;
    }

    // }}}
    // {{{ getCol()

    /**
     * Fetch a single column from a result set and return it as an
     * indexed array
     *
     * @param string $query  the SQL query
     * @param mixed  $col    which column to return (integer [column number,
     *                       starting at 0] or string [column name])
     * @param mixed  $params array, string or numeric data to be used in
     *                       execution of the statement.  Quantity of items
     *                       passed must match quantity of placeholders in
     *                       query:  meaning 1 placeholder for non-array
     *                       parameters or 1 placeholder per array element.
     *
     * @return array  an indexed array with the data from the first
     *                row at index 0, or a DB error code
     *
     * @see DB_common::query()
     * @access public
     */
    function &getCol($query, $col = 0, $params = array())
    {
        settype($params, 'array');
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);

            if (DB::isError($sth)) {
                return $sth;
            }

            $res =& $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res =& $this->query($query);
        }

        if (DB::isError($res)) {
            return $res;
        }

        $fetchmode = is_int($col) ? DB_FETCHMODE_ORDERED : DB_FETCHMODE_ASSOC;
        $ret = array();

        while (is_array($row = $res->fetchRow($fetchmode))) {
            $ret[] = $row[$col];
        }

        $res->free();

        if (DB::isError($row)) {
            $ret = $row;
        }

        return $ret;
    }

    // }}}
    // {{{ getAssoc()

    /**
     * Fetch the entire result set of a query and return it as an
     * associative array using the first column as the key
     *
     * If the result set contains more than two columns, the value
     * will be an array of the values from column 2-n.  If the result
     * set contains only two columns, the returned value will be a
     * scalar with the value of the second column (unless forced to an
     * array with the $force_array parameter).  A DB error code is
     * returned on errors.  If the result set contains fewer than two
     * columns, a DB_ERROR_TRUNCATED error is returned.
     *
     * For example, if the table "mytable" contains:
     *
     * <pre>
     *  ID      TEXT       DATE
     * --------------------------------
     *  1       'one'      944679408
     *  2       'two'      944679408
     *  3       'three'    944679408
     * </pre>
     *
     * Then the call getAssoc('SELECT id,text FROM mytable') returns:
     * <pre>
     *   array(
     *     '1' => 'one',
     *     '2' => 'two',
     *     '3' => 'three',
     *   )
     * </pre>
     *
     * ...while the call getAssoc('SELECT id,text,date FROM mytable') returns:
     * <pre>
     *   array(
     *     '1' => array('one', '944679408'),
     *     '2' => array('two', '944679408'),
     *     '3' => array('three', '944679408')
     *   )
     * </pre>
     *
     * If the more than one row occurs with the same value in the
     * first column, the last row overwrites all previous ones by
     * default.  Use the $group parameter if you don't want to
     * overwrite like this.  Example:
     *
     * <pre>
     * getAssoc('SELECT category,id,name FROM mytable', false, null,
     *          DB_FETCHMODE_ASSOC, true) returns:
     *
     *   array(
     *     '1' => array(array('id' => '4', 'name' => 'number four'),
     *                  array('id' => '6', 'name' => 'number six')
     *            ),
     *     '9' => array(array('id' => '4', 'name' => 'number four'),
     *                  array('id' => '6', 'name' => 'number six')
     *            )
     *   )
     * </pre>
     *
     * Keep in mind that database functions in PHP usually return string
     * values for results regardless of the database's internal type.
     *
     * @param string  $query  the SQL query
     * @param boolean $force_array  used only when the query returns
     *                              exactly two columns.  If true, the values
     *                              of the returned array will be one-element
     *                              arrays instead of scalars.
     * @param mixed   $params array, string or numeric data to be used in
     *                        execution of the statement.  Quantity of items
     *                        passed must match quantity of placeholders in
     *                        query:  meaning 1 placeholder for non-array
     *                        parameters or 1 placeholder per array element.
     * @param boolean $group  if true, the values of the returned array
     *                        is wrapped in another array.  If the same
     *                        key value (in the first column) repeats
     *                        itself, the values will be appended to
     *                        this array instead of overwriting the
     *                        existing values.
     *
     * @return array  associative array with results from the query.
     *                DB Error on failure.
     *
     * @access public
     */
    function &getAssoc($query, $force_array = false, $params = array(),
                       $fetchmode = DB_FETCHMODE_DEFAULT, $group = false)
    {
        settype($params, 'array');
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);

            if (DB::isError($sth)) {
                return $sth;
            }

            $res =& $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res =& $this->query($query);
        }

        if (DB::isError($res)) {
            return $res;
        }
        if ($fetchmode == DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        $cols = $res->numCols();

        if ($cols < 2) {
            $tmp =& $this->raiseError(DB_ERROR_TRUNCATED);
            return $tmp;
        }

        $results = array();

        if ($cols > 2 || $force_array) {
            // return array values
            // XXX this part can be optimized
            if ($fetchmode == DB_FETCHMODE_ASSOC) {
                while (is_array($row = $res->fetchRow(DB_FETCHMODE_ASSOC))) {
                    reset($row);
                    $key = current($row);
                    unset($row[key($row)]);
                    if ($group) {
                        $results[$key][] = $row;
                    } else {
                        $results[$key] = $row;
                    }
                }
            } elseif ($fetchmode == DB_FETCHMODE_OBJECT) {
                while ($row = $res->fetchRow(DB_FETCHMODE_OBJECT)) {
                    $arr = get_object_vars($row);
                    $key = current($arr);
                    if ($group) {
                        $results[$key][] = $row;
                    } else {
                        $results[$key] = $row;
                    }
                }
            } else {
                while (is_array($row = $res->fetchRow(DB_FETCHMODE_ORDERED))) {
                    // we shift away the first element to get
                    // indices running from 0 again
                    $key = array_shift($row);
                    if ($group) {
                        $results[$key][] = $row;
                    } else {
                        $results[$key] = $row;
                    }
                }
            }
            if (DB::isError($row)) {
                $results = $row;
            }
        } else {
            // return scalar values
            while (is_array($row = $res->fetchRow(DB_FETCHMODE_ORDERED))) {
                if ($group) {
                    $results[$row[0]][] = $row[1];
                } else {
                    $results[$row[0]] = $row[1];
                }
            }
            if (DB::isError($row)) {
                $results = $row;
            }
        }

        $res->free();

        return $results;
    }

    // }}}
    // {{{ getAll()

    /**
     * Fetch all the rows returned from a query
     *
     * @param string $query  the SQL query
     * @param array  $params array to be used in execution of the statement.
     *                       Quantity of array elements must match quantity
     *                       of placeholders in query.  This function does
     *                       NOT support scalars.
     * @param int    $fetchmode  the fetch mode to use
     *
     * @return array  an nested array.  DB error on failure.
     *
     * @access public
     */
    function &getAll($query,
                     $params = array(),
                     $fetchmode = DB_FETCHMODE_DEFAULT)
    {
        // compat check, the params and fetchmode parameters used to
        // have the opposite order
        if (!is_array($params)) {
            if (is_array($fetchmode)) {
                if ($params === null) {
                    $tmp = DB_FETCHMODE_DEFAULT;
                } else {
                    $tmp = $params;
                }
                $params = $fetchmode;
                $fetchmode = $tmp;
            } elseif ($params !== null) {
                $fetchmode = $params;
                $params = array();
            }
        }

        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);

            if (DB::isError($sth)) {
                return $sth;
            }

            $res =& $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res =& $this->query($query);
        }

        if (DB::isError($res) || $res == DB_OK) {
            return $res;
        }

        $results = array();
        while (DB_OK === $res->fetchInto($row, $fetchmode)) {
            if ($fetchmode & DB_FETCHMODE_FLIPPED) {
                foreach ($row as $key => $val) {
                    $results[$key][] = $val;
                }
            } else {
                $results[] = $row;
            }
        }

        $res->free();

        if (DB::isError($row)) {
            $tmp =& $this->raiseError($row);
            return $tmp;
        }
        return $results;
    }

    // }}}
    // {{{ autoCommit()

    /**
     * enable automatic Commit
     *
     * @param boolean $onoff
     * @return mixed DB_Error
     *
     * @access public
     */
    function autoCommit($onoff=false)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ commit()

    /**
     * starts a Commit
     *
     * @return mixed DB_Error
     *
     * @access public
     */
    function commit()
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ rollback()

    /**
     * starts a rollback
     *
     * @return mixed DB_Error
     *
     * @access public
     */
    function rollback()
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ numRows()

    /**
     * Returns the number of rows in a result object
     *
     * @param object DB_Result the result object to check
     *
     * @return mixed DB_Error or the number of rows
     *
     * @access public
     */
    function numRows($result)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ affectedRows()

    /**
     * Returns the affected rows of a query
     *
     * @return mixed DB_Error or number of rows
     *
     * @access public
     */
    function affectedRows()
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ errorNative()

    /**
     * Returns an errormessage, provides by the database
     *
     * @return mixed DB_Error or message
     *
     * @access public
     */
    function errorNative()
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ getSequenceName()

    /**
     * Generate the name used inside the database for a sequence
     *
     * The createSequence() docblock contains notes about storing sequence
     * names.
     *
     * @param string $sqn  the sequence's public name
     *
     * @return string  the sequence's name in the backend
     *
     * @see DB_common::createSequence(), DB_common::dropSequence(),
     *      DB_common::nextID(), DB_common::setOption()
     * @access private
     */
    function getSequenceName($sqn)
    {
        return sprintf($this->getOption('seqname_format'),
                       preg_replace('/[^a-z0-9_.]/i', '_', $sqn));
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
     * @see DB_common::createSequence(), DB_common::dropSequence(),
     *      DB_common::getSequenceName()
     * @access public
     */
    function nextId($seq_name, $ondemand = true)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ createSequence()

    /**
     * Creates a new sequence
     *
     * The name of a given sequence is determined by passing the string
     * provided in the <var>$seq_name</var> argument through PHP's sprintf()
     * function using the value from the <var>seqname_format</var> option as
     * the sprintf()'s format argument.
     *
     * <var>seqname_format</var> is set via setOption().
     *
     * @param string $seq_name  name of the new sequence
     *
     * @return int  DB_OK on success.  A DB_Error object is returned if
     *              problems arise.
     *
     * @see DB_common::dropSequence(), DB_common::getSequenceName(),
     *      DB_common::nextID()
     * @access public
     */
    function createSequence($seq_name)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
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
     * @see DB_common::createSequence(), DB_common::getSequenceName(),
     *      DB_common::nextID()
     * @access public
     */
    function dropSequence($seq_name)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set
     *
     * The format of the resulting array depends on which <var>$mode</var>
     * you select.  The sample output below is based on this query:
     * <pre>
     *    SELECT tblFoo.fldID, tblFoo.fldPhone, tblBar.fldId
     *    FROM tblFoo
     *    JOIN tblBar ON tblFoo.fldId = tblBar.fldId
     * </pre>
     *
     * <ul>
     * <li>
     *
     * <kbd>null</kbd> (default)
     *   <pre>
     *   [0] => Array (
     *       [table] => tblFoo
     *       [name] => fldId
     *       [type] => int
     *       [len] => 11
     *       [flags] => primary_key not_null
     *   )
     *   [1] => Array (
     *       [table] => tblFoo
     *       [name] => fldPhone
     *       [type] => string
     *       [len] => 20
     *       [flags] =>
     *   )
     *   [2] => Array (
     *       [table] => tblBar
     *       [name] => fldId
     *       [type] => int
     *       [len] => 11
     *       [flags] => primary_key not_null
     *   )
     *   </pre>
     *
     * </li><li>
     *
     * <kbd>DB_TABLEINFO_ORDER</kbd>
     *
     *   <p>In addition to the information found in the default output,
     *   a notation of the number of columns is provided by the
     *   <samp>num_fields</samp> element while the <samp>order</samp>
     *   element provides an array with the column names as the keys and
     *   their location index number (corresponding to the keys in the
     *   the default output) as the values.</p>
     *
     *   <p>If a result set has identical field names, the last one is
     *   used.</p>
     *
     *   <pre>
     *   [num_fields] => 3
     *   [order] => Array (
     *       [fldId] => 2
     *       [fldTrans] => 1
     *   )
     *   </pre>
     *
     * </li><li>
     *
     * <kbd>DB_TABLEINFO_ORDERTABLE</kbd>
     *
     *   <p>Similar to <kbd>DB_TABLEINFO_ORDER</kbd> but adds more
     *   dimensions to the array in which the table names are keys and
     *   the field names are sub-keys.  This is helpful for queries that
     *   join tables which have identical field names.</p>
     *
     *   <pre>
     *   [num_fields] => 3
     *   [ordertable] => Array (
     *       [tblFoo] => Array (
     *           [fldId] => 0
     *           [fldPhone] => 1
     *       )
     *       [tblBar] => Array (
     *           [fldId] => 2
     *       )
     *   )
     *   </pre>
     *
     * </li>
     * </ul>
     *
     * The <samp>flags</samp> element contains a space separated list
     * of extra information about the field.  This data is inconsistent
     * between DBMS's due to the way each DBMS works.
     *   + <samp>primary_key</samp>
     *   + <samp>unique_key</samp>
     *   + <samp>multiple_key</samp>
     *   + <samp>not_null</samp>
     *
     * Most DBMS's only provide the <samp>table</samp> and <samp>flags</samp>
     * elements if <var>$result</var> is a table name.  The following DBMS's
     * provide full information from queries:
     *   + fbsql
     *   + mysql
     *
     * If the 'portability' option has <samp>DB_PORTABILITY_LOWERCASE</samp>
     * turned on, the names of tables and fields will be lowercased.
     *
     * @param object|string  $result  DB_result object from a query or a
     *                                string containing the name of a table.
     *                                While this also accepts a query result
     *                                resource identifier, this behavior is
     *                                deprecated.
     * @param int  $mode   either unused or one of the tableInfo modes:
     *                     <kbd>DB_TABLEINFO_ORDERTABLE</kbd>,
     *                     <kbd>DB_TABLEINFO_ORDER</kbd> or
     *                     <kbd>DB_TABLEINFO_FULL</kbd> (which does both).
     *                     These are bitwise, so the first two can be
     *                     combined using <kbd>|</kbd>.
     * @return array  an associative array with the information requested.
     *                If something goes wrong an error object is returned.
     *
     * @see DB_common::setOption()
     * @access public
     */
    function tableInfo($result, $mode = null)
    {
        /*
         * If the DB_<driver> class has a tableInfo() method, that one
         * overrides this one.  But, if the driver doesn't have one,
         * this method runs and tells users about that fact.
         */
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ getTables()

    /**
     * @deprecated  Deprecated in release 1.2 or lower
     */
    function getTables()
    {
        return $this->getListOf('tables');
    }

    // }}}
    // {{{ getListOf()

    /**
     * list internal DB info
     * valid values for $type are db dependent,
     * often: databases, users, view, functions
     *
     * @param string $type type of requested info
     *
     * @return mixed DB_Error or the requested data
     *
     * @access public
     */
    function getListOf($type)
    {
        $sql = $this->getSpecialQuery($type);
        if ($sql === null) {                                // No support
            return $this->raiseError(DB_ERROR_UNSUPPORTED);
        } elseif (is_int($sql) || DB::isError($sql)) {      // Previous error
            return $this->raiseError($sql);
        } elseif (is_array($sql)) {                         // Already the result
            return $sql;
        }
        return $this->getCol($sql);                         // Launch this query
    }

    // }}}
    // {{{ getSpecialQuery()

    /**
     * Returns the query needed to get some backend info
     *
     * @param string $type What kind of info you want to retrieve
     *
     * @return string The SQL query string
     *
     * @access public
     */
    function getSpecialQuery($type)
    {
        return $this->raiseError(DB_ERROR_UNSUPPORTED);
    }

    // }}}
    // {{{ _rtrimArrayValues()

    /**
     * Right trim all strings in an array
     *
     * @param array  $array  the array to be trimmed (passed by reference)
     * @return void
     * @access private
     */
    function _rtrimArrayValues(&$array) {
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $array[$key] = rtrim($value);
            }
        }
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
