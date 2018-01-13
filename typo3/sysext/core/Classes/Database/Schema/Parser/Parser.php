<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Database\Schema\Parser;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Doctrine\DBAL\Schema\Table;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateTableStatement;

/**
 * An LL(*) recursive-descent parser for MySQL CREATE TABLE statements.
 * Parses a CREATE TABLE statement, reports any errors in it, and generates an AST.
 */
class Parser
{
    /**
     * The lexer.
     *
     * @var Lexer
     */
    protected $lexer;

    /**
     * The statement to parse.
     *
     * @var string
     */
    protected $statement;

    /**
     * Creates a new statement parser object.
     *
     * @param string $statement The statement to parse.
     */
    public function __construct(string $statement)
    {
        $this->statement = $statement;
        $this->lexer = new Lexer($statement);
    }

    /**
     * Gets the lexer used by the parser.
     *
     * @return Lexer
     */
    public function getLexer(): Lexer
    {
        return $this->lexer;
    }

    /**
     * Parses and builds AST for the given Query.
     *
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\AbstractCreateStatement
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    public function getAST(): AST\AbstractCreateStatement
    {
        // Parse & build AST
        return $this->queryLanguage();
    }

    /**
     * Attempts to match the given token with the current lookahead token.
     *
     * If they match, updates the lookahead token; otherwise raises a syntax
     * error.
     *
     * @param int $token The token type.
     *
     *
     * @throws StatementException If the tokens don't match.
     */
    public function match($token)
    {
        $lookaheadType = $this->lexer->lookahead['type'];

        // Short-circuit on first condition, usually types match
        if ($lookaheadType !== $token) {
            // If parameter is not identifier (1-99) must be exact match
            if ($token < Lexer::T_IDENTIFIER) {
                $this->syntaxError($this->lexer->getLiteral($token));
            }

            // If parameter is keyword (200+) must be exact match
            if ($token > Lexer::T_IDENTIFIER) {
                $this->syntaxError($this->lexer->getLiteral($token));
            }

            // If parameter is MATCH then FULL, PARTIAL or SIMPLE must follow
            if ($token === Lexer::T_MATCH
                && $lookaheadType !== Lexer::T_FULL
                && $lookaheadType !== Lexer::T_PARTIAL
                && $lookaheadType !== Lexer::T_SIMPLE
            ) {
                $this->syntaxError($this->lexer->getLiteral($token));
            }

            if ($token === Lexer::T_ON && $lookaheadType !== Lexer::T_DELETE && $lookaheadType !== Lexer::T_UPDATE) {
                $this->syntaxError($this->lexer->getLiteral($token));
            }
        }

        $this->lexer->moveNext();
    }

    /**
     * Frees this parser, enabling it to be reused.
     *
     * @param bool $deep Whether to clean peek and reset errors.
     * @param int $position Position to reset.
     */
    public function free($deep = false, $position = 0)
    {
        // WARNING! Use this method with care. It resets the scanner!
        $this->lexer->resetPosition($position);

        // Deep = true cleans peek and also any previously defined errors
        if ($deep) {
            $this->lexer->resetPeek();
        }

        $this->lexer->token = null;
        $this->lexer->lookahead = null;
    }

    /**
     * Parses a statement string.
     *
     * @return Table[]
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    public function parse(): array
    {
        $ast = $this->getAST();

        if (!$ast instanceof CreateTableStatement) {
            return [];
        }

        $tableBuilder = new TableBuilder();
        $table = $tableBuilder->create($ast);

        return [$table];
    }

    /**
     * Generates a new syntax error.
     *
     * @param string $expected Expected string.
     * @param array|null $token Got token.
     *
     *
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    public function syntaxError($expected = '', $token = null)
    {
        if ($token === null) {
            $token = $this->lexer->lookahead;
        }

        $tokenPos = $token['position'] ?? '-1';

        $message = "line 0, col {$tokenPos}: Error: ";
        $message .= ($expected !== '') ? "Expected {$expected}, got " : 'Unexpected ';
        $message .= ($this->lexer->lookahead === null) ? 'end of string.' : "'{$token['value']}'";

        throw StatementException::syntaxError($message, StatementException::sqlError($this->statement));
    }

    /**
     * Generates a new semantical error.
     *
     * @param string $message Optional message.
     * @param array|null $token Optional token.
     *
     *
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    public function semanticalError($message = '', $token = null)
    {
        if ($token === null) {
            $token = $this->lexer->lookahead;
        }

        // Minimum exposed chars ahead of token
        $distance = 12;

        // Find a position of a final word to display in error string
        $createTableStatement = $this->statement;
        $length = strlen($createTableStatement);
        $pos = $token['position'] + $distance;
        $pos = strpos($createTableStatement, ' ', ($length > $pos) ? $pos : $length);
        $length = ($pos !== false) ? $pos - $token['position'] : $distance;

        $tokenPos = array_key_exists('position', $token) && $token['position'] > 0 ? $token['position'] : '-1';
        $tokenStr = substr($createTableStatement, $token['position'], $length);

        // Building informative message
        $message = 'line 0, col ' . $tokenPos . " near '" . $tokenStr . "': Error: " . $message;

        throw StatementException::semanticalError($message, StatementException::sqlError($this->statement));
    }

    /**
     * Peeks beyond the matched closing parenthesis and returns the first token after that one.
     *
     * @param bool $resetPeek Reset peek after finding the closing parenthesis.
     *
     * @return array
     */
    protected function peekBeyondClosingParenthesis($resetPeek = true)
    {
        $token = $this->lexer->peek();
        $numUnmatched = 1;

        while ($numUnmatched > 0 && $token !== null) {
            switch ($token['type']) {
                case Lexer::T_OPEN_PARENTHESIS:
                    ++$numUnmatched;
                    break;
                case Lexer::T_CLOSE_PARENTHESIS:
                    --$numUnmatched;
                    break;
                default:
                    // Do nothing
            }

            $token = $this->lexer->peek();
        }

        if ($resetPeek) {
            $this->lexer->resetPeek();
        }

        return $token;
    }

    /**
     * queryLanguage ::= CreateTableStatement
     *
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\AbstractCreateStatement
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    public function queryLanguage(): AST\AbstractCreateStatement
    {
        $this->lexer->moveNext();

        if ($this->lexer->lookahead['type'] !== Lexer::T_CREATE) {
            $this->syntaxError('CREATE');
        }

        $statement = $this->createStatement();

        // Check for end of string
        if ($this->lexer->lookahead !== null) {
            $this->syntaxError('end of string');
        }

        return $statement;
    }

    /**
     * CreateStatement ::= CREATE [TEMPORARY] TABLE
     * Abstraction to allow for support of other schema objects like views in the future.
     *
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\AbstractCreateStatement
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    public function createStatement(): AST\AbstractCreateStatement
    {
        $statement = null;
        $this->match(Lexer::T_CREATE);

        switch ($this->lexer->lookahead['type']) {
            case Lexer::T_TEMPORARY:
                // Intentional fall-through
            case Lexer::T_TABLE:
                $statement = $this->createTableStatement();
                break;
            default:
                $this->syntaxError('TEMPORARY or TABLE');
                break;
        }

        $this->match(Lexer::T_SEMICOLON);

        return $statement;
    }

    /**
     * CreateTableStatement ::= CREATE [TEMPORARY] TABLE [IF NOT EXISTS] tbl_name (create_definition,...) [tbl_options]
     *
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function createTableStatement(): AST\CreateTableStatement
    {
        $createTableStatement = new AST\CreateTableStatement($this->createTableClause(), $this->createDefinition());

        if (!$this->lexer->isNextToken(Lexer::T_SEMICOLON)) {
            $createTableStatement->tableOptions = $this->tableOptions();
        }
        return $createTableStatement;
    }

    /**
     * CreateTableClause ::= CREATE [TEMPORARY] TABLE [IF NOT EXISTS] tbl_name
     *
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateTableClause
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function createTableClause(): AST\CreateTableClause
    {
        $isTemporary = false;
        // Check for TEMPORARY
        if ($this->lexer->isNextToken(Lexer::T_TEMPORARY)) {
            $this->match(Lexer::T_TEMPORARY);
            $isTemporary = true;
        }

        $this->match(Lexer::T_TABLE);

        // Check for IF NOT EXISTS
        if ($this->lexer->isNextToken(Lexer::T_IF)) {
            $this->match(Lexer::T_IF);
            $this->match(Lexer::T_NOT);
            $this->match(Lexer::T_EXISTS);
        }

        // Process schema object name (table name)
        $tableName = $this->schemaObjectName();

        return new AST\CreateTableClause($tableName, $isTemporary);
    }

    /**
     * Parses the table field/index definition
     *
     * createDefinition ::= (
     *  col_name column_definition
     *  | [CONSTRAINT [symbol]] PRIMARY KEY [index_type] (index_col_name,...) [index_option] ...
     *  | {INDEX|KEY} [index_name] [index_type] (index_col_name,...) [index_option] ...
     *  | [CONSTRAINT [symbol]] UNIQUE [INDEX|KEY] [index_name] [index_type] (index_col_name,...) [index_option] ...
     *  | {FULLTEXT|SPATIAL} [INDEX|KEY] [index_name] (index_col_name,...) [index_option] ...
     *  | [CONSTRAINT [symbol]] FOREIGN KEY [index_name] (index_col_name,...) reference_definition
     *  | CHECK (expr)
     * )
     *
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateDefinition
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function createDefinition(): AST\CreateDefinition
    {
        $createDefinitions = [];

        // Process opening parenthesis
        $this->match(Lexer::T_OPEN_PARENTHESIS);

        $createDefinitions[] = $this->createDefinitionItem();

        while ($this->lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);

            // TYPO3 previously accepted invalid SQL files where a create definition
            // item terminated with a comma before the final closing parenthesis.
            // Silently swallow the extra comma and stop the create definition parsing.
            if ($this->lexer->isNextToken(Lexer::T_CLOSE_PARENTHESIS)) {
                break;
            }

            $createDefinitions[] = $this->createDefinitionItem();
        }

        // Process closing parenthesis
        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return new AST\CreateDefinition($createDefinitions);
    }

    /**
     * Parse the definition of a single column or index
     *
     * @see createDefinition()
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\AbstractCreateDefinitionItem
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function createDefinitionItem(): AST\AbstractCreateDefinitionItem
    {
        $definitionItem = null;

        switch ($this->lexer->lookahead['type']) {
            case Lexer::T_FULLTEXT:
                // Intentional fall-through
            case Lexer::T_SPATIAL:
                // Intentional fall-through
            case Lexer::T_PRIMARY:
                // Intentional fall-through
            case Lexer::T_UNIQUE:
                // Intentional fall-through
            case Lexer::T_KEY:
                // Intentional fall-through
            case Lexer::T_INDEX:
                $definitionItem = $this->createIndexDefinitionItem();
                break;
            case Lexer::T_FOREIGN:
                $definitionItem = $this->createForeignKeyDefinitionItem();
                break;
            case Lexer::T_CONSTRAINT:
                $this->semanticalError('CONSTRAINT [symbol] index definition part not supported');
                break;
            case Lexer::T_CHECK:
                $this->semanticalError('CHECK (expr) create definition not supported');
                break;
            default:
                $definitionItem = $this->createColumnDefinitionItem();
        }

        return $definitionItem;
    }

    /**
     * Parses an index definition item contained in the create definition
     *
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateIndexDefinitionItem
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function createIndexDefinitionItem(): AST\CreateIndexDefinitionItem
    {
        $indexName = null;
        $isPrimary = false;
        $isFulltext = false;
        $isSpatial = false;
        $isUnique = false;
        $indexDefinition = new AST\CreateIndexDefinitionItem();

        switch ($this->lexer->lookahead['type']) {
            case Lexer::T_PRIMARY:
                $this->match(Lexer::T_PRIMARY);
                // KEY is a required keyword for PRIMARY index
                $this->match(Lexer::T_KEY);
                $isPrimary = true;
                break;
            case Lexer::T_KEY:
                // Plain index, no special configuration
                $this->match(Lexer::T_KEY);
                break;
            case Lexer::T_INDEX:
                // Plain index, no special configuration
                $this->match(Lexer::T_INDEX);
                break;
            case Lexer::T_UNIQUE:
                $this->match(Lexer::T_UNIQUE);
                // INDEX|KEY are optional keywords for UNIQUE index
                if ($this->lexer->isNextTokenAny([Lexer::T_INDEX, Lexer::T_KEY])) {
                    $this->lexer->moveNext();
                }
                $isUnique = true;
                break;
            case Lexer::T_FULLTEXT:
                $this->match(Lexer::T_FULLTEXT);
                // INDEX|KEY are optional keywords for FULLTEXT index
                if ($this->lexer->isNextTokenAny([Lexer::T_INDEX, Lexer::T_KEY])) {
                    $this->lexer->moveNext();
                }
                $isFulltext = true;
                break;
            case Lexer::T_SPATIAL:
                $this->match(Lexer::T_SPATIAL);
                // INDEX|KEY are optional keywords for SPATIAL index
                if ($this->lexer->isNextTokenAny([Lexer::T_INDEX, Lexer::T_KEY])) {
                    $this->lexer->moveNext();
                }
                $isSpatial = true;
                break;
            default:
                $this->syntaxError('PRIMARY, KEY, INDEX, UNIQUE, FULLTEXT or SPATIAL');
        }

        // PRIMARY KEY has no name in MySQL
        if (!$indexDefinition->isPrimary) {
            $indexName = $this->indexName();
        }

        $indexDefinition = new AST\CreateIndexDefinitionItem(
            $indexName,
            $isPrimary,
            $isUnique,
            $isSpatial,
            $isFulltext
        );

        // FULLTEXT and SPATIAL indexes can not have a type definition
        if (!$isFulltext && !$isSpatial) {
            $indexDefinition->indexType = $this->indexType();
        }

        $this->match(Lexer::T_OPEN_PARENTHESIS);

        $indexDefinition->columnNames[] = $this->indexColumnName();

        while ($this->lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $indexDefinition->columnNames[] = $this->indexColumnName();
        }

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        $indexDefinition->options = $this->indexOptions();

        return $indexDefinition;
    }

    /**
     * Parses an foreign key definition item contained in the create definition
     *
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateForeignKeyDefinitionItem
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function createForeignKeyDefinitionItem(): AST\CreateForeignKeyDefinitionItem
    {
        $this->match(Lexer::T_FOREIGN);
        $this->match(Lexer::T_KEY);

        $indexName = $this->indexName();

        $this->match(Lexer::T_OPEN_PARENTHESIS);

        $indexColumns = [];
        $indexColumns[] = $this->indexColumnName();

        while ($this->lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $indexColumns[] = $this->indexColumnName();
        }

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        $foreignKeyDefinition = new AST\CreateForeignKeyDefinitionItem(
            $indexName,
            $indexColumns,
            $this->referenceDefinition()
        );

        return $foreignKeyDefinition;
    }

    /**
     * Return the name of an index. No name has been supplied if the next token is USING
     * which defines the index type.
     *
     * @return AST\Identifier
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    public function indexName(): AST\Identifier
    {
        $indexName = new AST\Identifier(null);
        if (!$this->lexer->isNextTokenAny([Lexer::T_USING, Lexer::T_OPEN_PARENTHESIS])) {
            $indexName = $this->schemaObjectName();
        }

        return $indexName;
    }

    /**
     * IndexType ::= USING { BTREE | HASH }
     *
     * @return string
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    public function indexType(): string
    {
        $indexType = '';
        if (!$this->lexer->isNextToken(Lexer::T_USING)) {
            return $indexType;
        }

        $this->match(Lexer::T_USING);

        switch ($this->lexer->lookahead['type']) {
            case Lexer::T_BTREE:
                $this->match(Lexer::T_BTREE);
                $indexType = 'BTREE';
                break;
            case Lexer::T_HASH:
                $this->match(Lexer::T_HASH);
                $indexType = 'HASH';
                break;
            default:
                $this->syntaxError('BTREE or HASH');
        }

        return $indexType;
    }

    /**
     * IndexOptions ::=  KEY_BLOCK_SIZE [=] value
     *  | index_type
     *  | WITH PARSER parser_name
     *  | COMMENT 'string'
     *
     * @return array
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    public function indexOptions(): array
    {
        $options = [];

        while ($this->lexer->lookahead && !$this->lexer->isNextTokenAny([Lexer::T_COMMA, Lexer::T_CLOSE_PARENTHESIS])) {
            switch ($this->lexer->lookahead['type']) {
                case Lexer::T_KEY_BLOCK_SIZE:
                    $this->match(Lexer::T_KEY_BLOCK_SIZE);
                    if ($this->lexer->isNextToken(Lexer::T_EQUALS)) {
                        $this->match(Lexer::T_EQUALS);
                    }
                    $this->lexer->moveNext();
                    $options['key_block_size'] = (int)$this->lexer->token['value'];
                    break;
                case Lexer::T_USING:
                    $options['index_type'] = $this->indexType();
                    break;
                case Lexer::T_WITH:
                    $this->match(Lexer::T_WITH);
                    $this->match(Lexer::T_PARSER);
                    $options['parser'] = $this->schemaObjectName();
                    break;
                case Lexer::T_COMMENT:
                    $this->match(Lexer::T_COMMENT);
                    $this->match(Lexer::T_STRING);
                    $options['comment'] = $this->lexer->token['value'];
                    break;
                default:
                    $this->syntaxError('KEY_BLOCK_SIZE, USING, WITH PARSER or COMMENT');
            }
        }

        return $options;
    }

    /**
     * CreateColumnDefinitionItem ::= col_name column_definition
     *
     * column_definition:
     *   data_type [NOT NULL | NULL] [DEFAULT default_value]
     *     [AUTO_INCREMENT] [UNIQUE [KEY] | [PRIMARY] KEY]
     *     [COMMENT 'string']
     *     [COLUMN_FORMAT {FIXED|DYNAMIC|DEFAULT}]
     *     [STORAGE {DISK|MEMORY|DEFAULT}]
     *     [reference_definition]
     *
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\CreateColumnDefinitionItem
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function createColumnDefinitionItem(): AST\CreateColumnDefinitionItem
    {
        $columnName = $this->schemaObjectName();
        $dataType = $this->columnDataType();

        $columnDefinitionItem = new AST\CreateColumnDefinitionItem($columnName, $dataType);

        while ($this->lexer->lookahead && !$this->lexer->isNextTokenAny([Lexer::T_COMMA, Lexer::T_CLOSE_PARENTHESIS])) {
            switch ($this->lexer->lookahead['type']) {
                case Lexer::T_NOT:
                    $columnDefinitionItem->allowNull = false;
                    $this->match(Lexer::T_NOT);
                    $this->match(Lexer::T_NULL);
                    break;
                case Lexer::T_NULL:
                    $columnDefinitionItem->null = true;
                    $this->match(Lexer::T_NULL);
                    break;
                case Lexer::T_DEFAULT:
                    $columnDefinitionItem->hasDefaultValue = true;
                    $columnDefinitionItem->defaultValue = $this->columnDefaultValue();
                    break;
                case Lexer::T_AUTO_INCREMENT:
                    $columnDefinitionItem->autoIncrement = true;
                    $this->match(Lexer::T_AUTO_INCREMENT);
                    break;
                case Lexer::T_UNIQUE:
                    $columnDefinitionItem->unique = true;
                    $this->match(Lexer::T_UNIQUE);
                    if ($this->lexer->isNextToken(Lexer::T_KEY)) {
                        $this->match(Lexer::T_KEY);
                    }
                    break;
                case Lexer::T_PRIMARY:
                    $columnDefinitionItem->primary = true;
                    $this->match(Lexer::T_PRIMARY);
                    if ($this->lexer->isNextToken(Lexer::T_KEY)) {
                        $this->match(Lexer::T_KEY);
                    }
                    break;
                case Lexer::T_KEY:
                    $columnDefinitionItem->index = true;
                    $this->match(Lexer::T_KEY);
                    break;
                case Lexer::T_COMMENT:
                    $this->match(Lexer::T_COMMENT);
                    if ($this->lexer->isNextToken(Lexer::T_STRING)) {
                        $columnDefinitionItem->comment = $this->lexer->lookahead['value'];
                        $this->match(Lexer::T_STRING);
                    }
                    break;
                case Lexer::T_COLUMN_FORMAT:
                    $this->match(Lexer::T_COLUMN_FORMAT);
                    if ($this->lexer->isNextToken(Lexer::T_FIXED)) {
                        $columnDefinitionItem->columnFormat = 'fixed';
                        $this->match(Lexer::T_FIXED);
                    } elseif ($this->lexer->isNextToken(Lexer::T_DYNAMIC)) {
                        $columnDefinitionItem->columnFormat = 'dynamic';
                        $this->match(Lexer::T_DYNAMIC);
                    } else {
                        $this->match(Lexer::T_DEFAULT);
                    }
                    break;
                case Lexer::T_STORAGE:
                    $this->match(Lexer::T_STORAGE);
                    if ($this->lexer->isNextToken(Lexer::T_MEMORY)) {
                        $columnDefinitionItem->storage = 'memory';
                        $this->match(Lexer::T_MEMORY);
                    } elseif ($this->lexer->isNextToken(Lexer::T_DISK)) {
                        $columnDefinitionItem->storage = 'disk';
                        $this->match(Lexer::T_DISK);
                    } else {
                        $this->match(Lexer::T_DEFAULT);
                    }
                    break;
                case Lexer::T_REFERENCES:
                    $columnDefinitionItem->reference = $this->referenceDefinition();
                    break;
                default:
                    $this->syntaxError(
                        'NOT, NULL, DEFAULT, AUTO_INCREMENT, UNIQUE, ' .
                        'PRIMARY, COMMENT, COLUMN_FORMAT, STORAGE or REFERENCES'
                    );
            }
        }

        return $columnDefinitionItem;
    }

    /**
     * DataType ::= BIT[(length)]
     *   | TINYINT[(length)] [UNSIGNED] [ZEROFILL]
     *   | SMALLINT[(length)] [UNSIGNED] [ZEROFILL]
     *   | MEDIUMINT[(length)] [UNSIGNED] [ZEROFILL]
     *   | INT[(length)] [UNSIGNED] [ZEROFILL]
     *   | INTEGER[(length)] [UNSIGNED] [ZEROFILL]
     *   | BIGINT[(length)] [UNSIGNED] [ZEROFILL]
     *   | REAL[(length,decimals)] [UNSIGNED] [ZEROFILL]
     *   | DOUBLE[(length,decimals)] [UNSIGNED] [ZEROFILL]
     *   | FLOAT[(length,decimals)] [UNSIGNED] [ZEROFILL]
     *   | DECIMAL[(length[,decimals])] [UNSIGNED] [ZEROFILL]
     *   | NUMERIC[(length[,decimals])] [UNSIGNED] [ZEROFILL]
     *   | DATE
     *   | TIME[(fsp)]
     *   | TIMESTAMP[(fsp)]
     *   | DATETIME[(fsp)]
     *   | YEAR
     *   | CHAR[(length)] [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | VARCHAR(length) [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | BINARY[(length)]
     *   | VARBINARY(length)
     *   | TINYBLOB
     *   | BLOB
     *   | MEDIUMBLOB
     *   | LONGBLOB
     *   | TINYTEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | TEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | MEDIUMTEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | LONGTEXT [BINARY] [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | ENUM(value1,value2,value3,...) [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | SET(value1,value2,value3,...) [CHARACTER SET charset_name] [COLLATE collation_name]
     *   | JSON
     *
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType\AbstractDataType
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function columnDataType(): AST\DataType\AbstractDataType
    {
        $dataType = null;

        switch ($this->lexer->lookahead['type']) {
            case Lexer::T_BIT:
                $this->match(Lexer::T_BIT);
                $dataType = new AST\DataType\BitDataType(
                    $this->dataTypeLength()
                );
                break;
            case Lexer::T_TINYINT:
                $this->match(Lexer::T_TINYINT);
                $dataType = new AST\DataType\TinyIntDataType(
                    $this->dataTypeLength(),
                    $this->numericDataTypeOptions()
                );
                break;
            case Lexer::T_SMALLINT:
                $this->match(Lexer::T_SMALLINT);
                $dataType = new AST\DataType\SmallIntDataType(
                    $this->dataTypeLength(),
                    $this->numericDataTypeOptions()
                );
                break;
            case Lexer::T_MEDIUMINT:
                $this->match(Lexer::T_MEDIUMINT);
                $dataType = new AST\DataType\MediumIntDataType(
                    $this->dataTypeLength(),
                    $this->numericDataTypeOptions()
                );
                break;
            case Lexer::T_INT:
                $this->match(Lexer::T_INT);
                $dataType = new AST\DataType\IntegerDataType(
                    $this->dataTypeLength(),
                    $this->numericDataTypeOptions()
                );
                break;
            case Lexer::T_INTEGER:
                $this->match(Lexer::T_INTEGER);
                $dataType = new AST\DataType\IntegerDataType(
                    $this->dataTypeLength(),
                    $this->numericDataTypeOptions()
                );
                break;
            case Lexer::T_BIGINT:
                $this->match(Lexer::T_BIGINT);
                $dataType = new AST\DataType\BigIntDataType(
                    $this->dataTypeLength(),
                    $this->numericDataTypeOptions()
                );
                break;
            case Lexer::T_REAL:
                $this->match(Lexer::T_REAL);
                $dataType = new AST\DataType\RealDataType(
                    $this->dataTypeDecimals(),
                    $this->numericDataTypeOptions()
                );
                break;
            case Lexer::T_DOUBLE:
                $this->match(Lexer::T_DOUBLE);
                if ($this->lexer->isNextToken(Lexer::T_PRECISION)) {
                    $this->match(Lexer::T_PRECISION);
                }
                $dataType = new AST\DataType\DoubleDataType(
                    $this->dataTypeDecimals(),
                    $this->numericDataTypeOptions()
                );
                break;
            case Lexer::T_FLOAT:
                $this->match(Lexer::T_FLOAT);
                $dataType = new AST\DataType\FloatDataType(
                    $this->dataTypeDecimals(),
                    $this->numericDataTypeOptions()
                );

                break;
            case Lexer::T_DECIMAL:
                $this->match(Lexer::T_DECIMAL);
                $dataType = new AST\DataType\DecimalDataType(
                    $this->dataTypeDecimals(),
                    $this->numericDataTypeOptions()
                );
                break;
            case Lexer::T_NUMERIC:
                $this->match(Lexer::T_NUMERIC);
                $dataType = new AST\DataType\NumericDataType(
                    $this->dataTypeDecimals(),
                    $this->numericDataTypeOptions()
                );
                break;
            case Lexer::T_DATE:
                $this->match(Lexer::T_DATE);
                $dataType = new AST\DataType\DateDataType();
                break;
            case Lexer::T_TIME:
                $this->match(Lexer::T_TIME);
                $dataType = new AST\DataType\TimeDataType($this->fractionalSecondsPart());
                break;
            case Lexer::T_TIMESTAMP:
                $this->match(Lexer::T_TIMESTAMP);
                $dataType = new AST\DataType\TimestampDataType($this->fractionalSecondsPart());
                break;
            case Lexer::T_DATETIME:
                $this->match(Lexer::T_DATETIME);
                $dataType = new AST\DataType\DateTimeDataType($this->fractionalSecondsPart());
                break;
            case Lexer::T_YEAR:
                $this->match(Lexer::T_YEAR);
                $dataType = new AST\DataType\YearDataType();
                break;
            case Lexer::T_CHAR:
                $this->match(Lexer::T_CHAR);
                $dataType = new AST\DataType\CharDataType(
                    $this->dataTypeLength(),
                    $this->characterDataTypeOptions()
                );
                break;
            case Lexer::T_VARCHAR:
                $this->match(Lexer::T_VARCHAR);
                $dataType = new AST\DataType\VarCharDataType(
                    $this->dataTypeLength(true),
                    $this->characterDataTypeOptions()
                );
                break;
            case Lexer::T_BINARY:
                $this->match(Lexer::T_BINARY);
                $dataType = new AST\DataType\BinaryDataType($this->dataTypeLength());
                break;
            case Lexer::T_VARBINARY:
                $this->match(Lexer::T_VARBINARY);
                $dataType = new AST\DataType\VarBinaryDataType($this->dataTypeLength(true));
                break;
            case Lexer::T_TINYBLOB:
                $this->match(Lexer::T_TINYBLOB);
                $dataType = new AST\DataType\TinyBlobDataType();
                break;
            case Lexer::T_BLOB:
                $this->match(Lexer::T_BLOB);
                $dataType = new AST\DataType\BlobDataType();
                break;
            case Lexer::T_MEDIUMBLOB:
                $this->match(Lexer::T_MEDIUMBLOB);
                $dataType = new AST\DataType\MediumBlobDataType();
                break;
            case Lexer::T_LONGBLOB:
                $this->match(Lexer::T_LONGBLOB);
                $dataType = new AST\DataType\LongBlobDataType();
                break;
            case Lexer::T_TINYTEXT:
                $this->match(Lexer::T_TINYTEXT);
                $dataType = new AST\DataType\TinyTextDataType($this->characterDataTypeOptions());
                break;
            case Lexer::T_TEXT:
                $this->match(Lexer::T_TEXT);
                $dataType = new AST\DataType\TextDataType($this->characterDataTypeOptions());
                break;
            case Lexer::T_MEDIUMTEXT:
                $this->match(Lexer::T_MEDIUMTEXT);
                $dataType = new AST\DataType\MediumTextDataType($this->characterDataTypeOptions());
                break;
            case Lexer::T_LONGTEXT:
                $this->match(Lexer::T_LONGTEXT);
                $dataType = new AST\DataType\LongTextDataType($this->characterDataTypeOptions());
                break;
            case Lexer::T_ENUM:
                $this->match(Lexer::T_ENUM);
                $dataType = new AST\DataType\EnumDataType($this->valueList(), $this->enumerationDataTypeOptions());
                break;
            case Lexer::T_SET:
                $this->match(Lexer::T_SET);
                $dataType = new AST\DataType\SetDataType($this->valueList(), $this->enumerationDataTypeOptions());
                break;
            case Lexer::T_JSON:
                $this->match(Lexer::T_JSON);
                $dataType = new AST\DataType\JsonDataType();
                break;
            default:
                $this->syntaxError(
                    'BIT, TINYINT, SMALLINT, MEDIUMINT, INT, INTEGER, BIGINT, REAL, DOUBLE, FLOAT, DECIMAL, NUMERIC, ' .
                    'DATE, TIME, TIMESTAMP, DATETIME, YEAR, CHAR, VARCHAR, BINARY, VARBINARY, TINYBLOB, BLOB, ' .
                    'MEDIUMBLOB, LONGBLOB, TINYTEXT, TEXT, MEDIUMTEXT, LONGTEXT, ENUM, SET, or JSON'
                );
        }

        return $dataType;
    }

    /**
     * DefaultValue::= DEFAULT default_value
     *
     * @return mixed
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function columnDefaultValue()
    {
        $this->match(Lexer::T_DEFAULT);
        $value = null;

        switch ($this->lexer->lookahead['type']) {
            case Lexer::T_INTEGER:
                $value = (int)$this->lexer->lookahead['value'];
                break;
            case Lexer::T_FLOAT:
                $value = (float)$this->lexer->lookahead['value'];
                break;
            case Lexer::T_STRING:
                $value = (string)$this->lexer->lookahead['value'];
                break;
            case Lexer::T_CURRENT_TIMESTAMP:
                $value = 'CURRENT_TIMESTAMP';
                break;
            case Lexer::T_NULL:
                $value = null;
                break;
            default:
                $this->syntaxError('String, Integer, Float, NULL or CURRENT_TIMESTAMP');
        }

        $this->lexer->moveNext();

        return $value;
    }

    /**
     * Determine length parameter of a column field definition, i.E. INT(11) or VARCHAR(255)
     *
     * @param bool $required
     * @return int
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function dataTypeLength(bool $required = false): int
    {
        $length = 0;
        if (!$this->lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
            if ($required) {
                $this->semanticalError('The current data type requires a field length definition.');
            }
            return $length;
        }

        $this->match(Lexer::T_OPEN_PARENTHESIS);
        $length = (int)$this->lexer->lookahead['value'];
        $this->match(Lexer::T_INTEGER);
        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return $length;
    }

    /**
     * Determine length and optional decimal parameter of a column field definition, i.E. DECIMAL(10,6)
     *
     * @return array
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    private function dataTypeDecimals(): array
    {
        $options = [];
        if (!$this->lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
            return $options;
        }

        $this->match(Lexer::T_OPEN_PARENTHESIS);
        $options['length'] = (int)$this->lexer->lookahead['value'];
        $this->match(Lexer::T_INTEGER);

        if ($this->lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $options['decimals'] = (int)$this->lexer->lookahead['value'];
            $this->match(Lexer::T_INTEGER);
        }

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return $options;
    }

    /**
     * Parse common options for numeric datatypes
     *
     * @return array
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function numericDataTypeOptions(): array
    {
        $options = ['unsigned' => false, 'zerofill' => false];

        if (!$this->lexer->isNextTokenAny([Lexer::T_UNSIGNED, Lexer::T_ZEROFILL])) {
            return $options;
        }

        while ($this->lexer->isNextTokenAny([Lexer::T_UNSIGNED, Lexer::T_ZEROFILL])) {
            switch ($this->lexer->lookahead['type']) {
                case Lexer::T_UNSIGNED:
                    $this->match(Lexer::T_UNSIGNED);
                    $options['unsigned'] = true;
                    break;
                case Lexer::T_ZEROFILL:
                    $this->match(Lexer::T_ZEROFILL);
                    $options['zerofill'] = true;
                    break;
                default:
                    $this->syntaxError('USIGNED or ZEROFILL');
            }
        }

        return $options;
    }

    /**
     * Determine the fractional seconds part support for TIME, DATETIME and TIMESTAMP columns
     *
     * @return int
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function fractionalSecondsPart(): int
    {
        $fractionalSecondsPart = $this->dataTypeLength();
        if ($fractionalSecondsPart < 0) {
            $this->semanticalError('the fractional seconds part for TIME, DATETIME or TIMESTAMP columns must >= 0');
        }
        if ($fractionalSecondsPart > 6) {
            $this->semanticalError('the fractional seconds part for TIME, DATETIME or TIMESTAMP columns must <= 6');
        }

        return $fractionalSecondsPart;
    }

    /**
     * Parse common options for numeric datatypes
     *
     * @return array
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function characterDataTypeOptions(): array
    {
        $options = ['binary' => false, 'charset' => null, 'collation' => null];

        if (!$this->lexer->isNextTokenAny([Lexer::T_CHARACTER, Lexer::T_COLLATE, Lexer::T_BINARY])) {
            return $options;
        }

        while ($this->lexer->isNextTokenAny([Lexer::T_CHARACTER, Lexer::T_COLLATE, Lexer::T_BINARY])) {
            switch ($this->lexer->lookahead['type']) {
                case Lexer::T_BINARY:
                    $this->match(Lexer::T_BINARY);
                    $options['binary'] = true;
                    break;
                case Lexer::T_CHARACTER:
                    $this->match(Lexer::T_CHARACTER);
                    $this->match(Lexer::T_SET);
                    $this->match(Lexer::T_STRING);
                    $options['charset'] = $this->lexer->token['value'];
                    break;
                case Lexer::T_COLLATE:
                    $this->match(Lexer::T_COLLATE);
                    $this->match(Lexer::T_STRING);
                    $options['collation'] = $this->lexer->token['value'];
                    break;
                default:
                    $this->syntaxError('BINARY, CHARACTER SET or COLLATE');
            }
        }

        return $options;
    }

    /**
     * Parse shared options for enumeration datatypes (ENUM and SET)
     *
     * @return array
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function enumerationDataTypeOptions(): array
    {
        $options = ['charset' => null, 'collation' => null];

        if (!$this->lexer->isNextTokenAny([Lexer::T_CHARACTER, Lexer::T_COLLATE])) {
            return $options;
        }

        while ($this->lexer->isNextTokenAny([Lexer::T_CHARACTER, Lexer::T_COLLATE])) {
            switch ($this->lexer->lookahead['type']) {
                case Lexer::T_CHARACTER:
                    $this->match(Lexer::T_CHARACTER);
                    $this->match(Lexer::T_SET);
                    $this->match(Lexer::T_STRING);
                    $options['charset'] = $this->lexer->token['value'];
                    break;
                case Lexer::T_COLLATE:
                    $this->match(Lexer::T_COLLATE);
                    $this->match(Lexer::T_STRING);
                    $options['collation'] = $this->lexer->token['value'];
                    break;
                default:
                    $this->syntaxError('CHARACTER SET or COLLATE');
            }
        }

        return $options;
    }

    /**
     * Return all defined values for an enumeration datatype (ENUM, SET)
     *
     * @return array
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function valueList(): array
    {
        $this->match(Lexer::T_OPEN_PARENTHESIS);

        $values = [];
        $values[] = $this->valueListItem();

        while ($this->lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $values[] = $this->valueListItem();
        }

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return $values;
    }

    /**
     * Return a value list item for an enumeration set
     *
     * @return string
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function valueListItem(): string
    {
        $this->match(Lexer::T_STRING);

        return (string)$this->lexer->token['value'];
    }

    /**
     * ReferenceDefinition ::= REFERENCES tbl_name (index_col_name,...)
     *  [MATCH FULL | MATCH PARTIAL | MATCH SIMPLE]
     *  [ON DELETE reference_option]
     *  [ON UPDATE reference_option]
     *
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\ReferenceDefinition
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function referenceDefinition(): AST\ReferenceDefinition
    {
        $this->match(Lexer::T_REFERENCES);
        $tableName = $this->schemaObjectName();
        $this->match(Lexer::T_OPEN_PARENTHESIS);

        $referenceColumns = [];
        $referenceColumns[] = $this->indexColumnName();

        while ($this->lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $referenceColumns[] = $this->indexColumnName();
        }

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        $referenceDefinition = new AST\ReferenceDefinition($tableName, $referenceColumns);

        while (!$this->lexer->isNextTokenAny([Lexer::T_COMMA, Lexer::T_CLOSE_PARENTHESIS])) {
            switch ($this->lexer->lookahead['type']) {
                case Lexer::T_MATCH:
                    $this->match(Lexer::T_MATCH);
                    $referenceDefinition->match = $this->lexer->lookahead['value'];
                    $this->lexer->moveNext();
                    break;
                case Lexer::T_ON:
                    $this->match(Lexer::T_ON);
                    if ($this->lexer->isNextToken(Lexer::T_DELETE)) {
                        $this->match(Lexer::T_DELETE);
                        $referenceDefinition->onDelete = $this->referenceOption();
                    } else {
                        $this->match(Lexer::T_UPDATE);
                        $referenceDefinition->onUpdate = $this->referenceOption();
                    }
                    break;
                default:
                    $this->syntaxError('MATCH, ON DELETE or ON UPDATE');
            }
        }

        return $referenceDefinition;
    }

    /**
     * IndexColumnName ::= col_name [(length)] [ASC | DESC]
     *
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\IndexColumnName
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function indexColumnName(): AST\IndexColumnName
    {
        $columnName = $this->schemaObjectName();
        $length = $this->dataTypeLength();
        $direction = null;

        if ($this->lexer->isNextToken(Lexer::T_ASC)) {
            $this->match(Lexer::T_ASC);
            $direction = 'ASC';
        } elseif ($this->lexer->isNextToken(Lexer::T_DESC)) {
            $this->match(Lexer::T_DESC);
            $direction = 'DESC';
        }

        return new AST\IndexColumnName($columnName, $length, $direction);
    }

    /**
     * ReferenceOption ::= RESTRICT | CASCADE | SET NULL | NO ACTION
     *
     * @return string
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function referenceOption(): string
    {
        $action = null;

        switch ($this->lexer->lookahead['type']) {
            case Lexer::T_RESTRICT:
                $this->match(Lexer::T_RESTRICT);
                $action = 'RESTRICT';
                break;
            case Lexer::T_CASCADE:
                $this->match(Lexer::T_CASCADE);
                $action = 'CASCADE';
                break;
            case Lexer::T_SET:
                $this->match(Lexer::T_SET);
                $this->match(Lexer::T_NULL);
                $action = 'SET NULL';
                break;
            case Lexer::T_NO:
                $this->match(Lexer::T_NO);
                $this->match(Lexer::T_ACTION);
                $action = 'NO ACTION';
                break;
            default:
                $this->syntaxError('RESTRICT, CASCADE, SET NULL or NO ACTION');
        }

        return $action;
    }

    /**
     * Parse MySQL table options
     *
     *  ENGINE [=] engine_name
     *  | AUTO_INCREMENT [=] value
     *  | AVG_ROW_LENGTH [=] value
     *  | [DEFAULT] CHARACTER SET [=] charset_name
     *  | CHECKSUM [=] {0 | 1}
     *  | [DEFAULT] COLLATE [=] collation_name
     *  | COMMENT [=] 'string'
     *  | COMPRESSION [=] {'ZLIB'|'LZ4'|'NONE'}
     *  | CONNECTION [=] 'connect_string'
     *  | DATA DIRECTORY [=] 'absolute path to directory'
     *  | DELAY_KEY_WRITE [=] {0 | 1}
     *  | ENCRYPTION [=] {'Y' | 'N'}
     *  | INDEX DIRECTORY [=] 'absolute path to directory'
     *  | INSERT_METHOD [=] { NO | FIRST | LAST }
     *  | KEY_BLOCK_SIZE [=] value
     *  | MAX_ROWS [=] value
     *  | MIN_ROWS [=] value
     *  | PACK_KEYS [=] {0 | 1 | DEFAULT}
     *  | PASSWORD [=] 'string'
     *  | ROW_FORMAT [=] {DEFAULT|DYNAMIC|FIXED|COMPRESSED|REDUNDANT|COMPACT}
     *  | STATS_AUTO_RECALC [=] {DEFAULT|0|1}
     *  | STATS_PERSISTENT [=] {DEFAULT|0|1}
     *  | STATS_SAMPLE_PAGES [=] value
     *  | TABLESPACE tablespace_name
     *  | UNION [=] (tbl_name[,tbl_name]...)
     *
     * @return array
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function tableOptions(): array
    {
        $options = [];

        while ($this->lexer->lookahead && !$this->lexer->isNextToken(Lexer::T_SEMICOLON)) {
            switch ($this->lexer->lookahead['type']) {
                case Lexer::T_DEFAULT:
                    // DEFAULT prefix is optional for COLLATE/CHARACTER SET, do nothing
                    $this->match(Lexer::T_DEFAULT);
                    break;
                case Lexer::T_ENGINE:
                    $this->match(Lexer::T_ENGINE);
                    $options['engine'] = (string)$this->tableOptionValue();
                    break;
                case Lexer::T_AUTO_INCREMENT:
                    $this->match(Lexer::T_AUTO_INCREMENT);
                    $options['auto_increment'] = (int)$this->tableOptionValue();
                    break;
                case Lexer::T_AVG_ROW_LENGTH:
                    $this->match(Lexer::T_AVG_ROW_LENGTH);
                    $options['average_row_length'] = (int)$this->tableOptionValue();
                    break;
                case Lexer::T_CHARACTER:
                    $this->match(Lexer::T_CHARACTER);
                    $this->match(Lexer::T_SET);
                    $options['character_set'] = (string)$this->tableOptionValue();
                    break;
                case Lexer::T_CHECKSUM:
                    $this->match(Lexer::T_CHECKSUM);
                    $options['checksum'] = (int)$this->tableOptionValue();
                    break;
                case Lexer::T_COLLATE:
                    $this->match(Lexer::T_COLLATE);
                    $options['collation'] = (string)$this->tableOptionValue();
                    break;
                case Lexer::T_COMMENT:
                    $this->match(Lexer::T_COMMENT);
                    $options['comment'] = (string)$this->tableOptionValue();
                    break;
                case Lexer::T_COMPRESSION:
                    $this->match(Lexer::T_COMPRESSION);
                    $options['compression'] = strtoupper((string)$this->tableOptionValue());
                    if (!in_array($options['compression'], ['ZLIB', 'LZ4', 'NONE'], true)) {
                        $this->syntaxError('ZLIB, LZ4 or NONE', $this->lexer->token);
                    }
                    break;
                case Lexer::T_CONNECTION:
                    $this->match(Lexer::T_CONNECTION);
                    $options['connection'] = (string)$this->tableOptionValue();
                    break;
                case Lexer::T_DATA:
                    $this->match(Lexer::T_DATA);
                    $this->match(Lexer::T_DIRECTORY);
                    $options['data_directory'] = (string)$this->tableOptionValue();
                    break;
                case Lexer::T_DELAY_KEY_WRITE:
                    $this->match(Lexer::T_DELAY_KEY_WRITE);
                    $options['delay_key_write'] = (int)$this->tableOptionValue();
                    break;
                case Lexer::T_ENCRYPTION:
                    $this->match(Lexer::T_ENCRYPTION);
                    $options['encryption'] = strtoupper((string)$this->tableOptionValue());
                    if (!in_array($options['encryption'], ['Y', 'N'], true)) {
                        $this->syntaxError('Y or N', $this->lexer->token);
                    }
                    break;
                case Lexer::T_INDEX:
                    $this->match(Lexer::T_INDEX);
                    $this->match(Lexer::T_DIRECTORY);
                    $options['index_directory'] = (string)$this->tableOptionValue();
                    break;
                case Lexer::T_INSERT_METHOD:
                    $this->match(Lexer::T_INSERT_METHOD);
                    $options['insert_method'] = strtoupper((string)$this->tableOptionValue());
                    if (!in_array($options['insert_method'], ['NO', 'FIRST', 'LAST'], true)) {
                        $this->syntaxError('NO, FIRST or LAST', $this->lexer->token);
                    }
                    break;
                case Lexer::T_KEY_BLOCK_SIZE:
                    $this->match(Lexer::T_KEY_BLOCK_SIZE);
                    $options['key_block_size'] = (int)$this->tableOptionValue();
                    break;
                case Lexer::T_MAX_ROWS:
                    $this->match(Lexer::T_MAX_ROWS);
                    $options['max_rows'] = (int)$this->tableOptionValue();
                    break;
                case Lexer::T_MIN_ROWS:
                    $this->match(Lexer::T_MIN_ROWS);
                    $options['min_rows'] = (int)$this->tableOptionValue();
                    break;
                case Lexer::T_PACK_KEYS:
                    $this->match(Lexer::T_PACK_KEYS);
                    $options['pack_keys'] = strtoupper((string)$this->tableOptionValue());
                    if (!in_array($options['pack_keys'], ['0', '1', 'DEFAULT'], true)) {
                        $this->syntaxError('0, 1 or DEFAULT', $this->lexer->token);
                    }
                    break;
                case Lexer::T_PASSWORD:
                    $this->match(Lexer::T_PASSWORD);
                    $options['password'] = (string)$this->tableOptionValue();
                    break;
                case Lexer::T_ROW_FORMAT:
                    $this->match(Lexer::T_ROW_FORMAT);
                    $options['row_format'] = (string)$this->tableOptionValue();
                    $validRowFormats = ['DEFAULT', 'DYNAMIC', 'FIXED', 'COMPRESSED', 'REDUNDANT', 'COMPACT'];
                    if (!in_array($options['row_format'], $validRowFormats, true)) {
                        $this->syntaxError(
                            'DEFAULT, DYNAMIC, FIXED, COMPRESSED, REDUNDANT, COMPACT',
                            $this->lexer->token
                        );
                    }
                    break;
                case Lexer::T_STATS_AUTO_RECALC:
                    $this->match(Lexer::T_STATS_AUTO_RECALC);
                    $options['stats_auto_recalc'] = strtoupper((string)$this->tableOptionValue());
                    if (!in_array($options['stats_auto_recalc'], ['0', '1', 'DEFAULT'], true)) {
                        $this->syntaxError('0, 1 or DEFAULT', $this->lexer->token);
                    }
                    break;
                case Lexer::T_STATS_PERSISTENT:
                    $this->match(Lexer::T_STATS_PERSISTENT);
                    $options['stats_persistent'] = strtoupper((string)$this->tableOptionValue());
                    if (!in_array($options['stats_persistent'], ['0', '1', 'DEFAULT'], true)) {
                        $this->syntaxError('0, 1 or DEFAULT', $this->lexer->token);
                    }
                    break;
                case Lexer::T_STATS_SAMPLE_PAGES:
                    $this->match(Lexer::T_STATS_SAMPLE_PAGES);
                    $options['stats_sample_pages'] = strtoupper((string)$this->tableOptionValue());
                    if (!in_array($options['stats_sample_pages'], ['0', '1', 'DEFAULT'], true)) {
                        $this->syntaxError('0, 1 or DEFAULT', $this->lexer->token);
                    }
                    break;
                case Lexer::T_TABLESPACE:
                    $this->match(Lexer::T_TABLESPACE);
                    $options['tablespace'] = (string)$this->tableOptionValue();
                    break;
                default:
                    $this->syntaxError(
                        'DEFAULT, ENGINE, AUTO_INCREMENT, AVG_ROW_LENGTH, CHARACTER SET, ' .
                        'CHECKSUM, COLLATE, COMMENT, COMPRESSION, CONNECTION, DATA DIRECTORY, ' .
                        'DELAY_KEY_WRITE, ENCRYPTION, INDEX DIRECTORY, INSERT_METHOD, KEY_BLOCK_SIZE, ' .
                        'MAX_ROWS, MIN_ROWS, PACK_KEYS, PASSWORD, ROW_FORMAT, STATS_AUTO_RECALC, ' .
                        'STATS_PERSISTENT, STATS_SAMPLE_PAGES or TABLESPACE'
                    );
            }
        }

        return $options;
    }

    /**
     * Return the value of an option, skipping the optional equal sign.
     *
     * @return mixed
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function tableOptionValue()
    {
        // Skip the optional equals sign
        if ($this->lexer->isNextToken(Lexer::T_EQUALS)) {
            $this->match(Lexer::T_EQUALS);
        }
        $this->lexer->moveNext();

        return $this->lexer->token['value'];
    }

    /**
     * Certain objects within MySQL, including database, table, index, column, alias, view, stored procedure,
     * partition, tablespace, and other object names are known as identifiers.
     *
     * @return \TYPO3\CMS\Core\Database\Schema\Parser\AST\Identifier
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function schemaObjectName()
    {
        $schemaObjectName = $this->lexer->lookahead['value'];
        $this->lexer->moveNext();

        return new AST\Identifier((string)$schemaObjectName);
    }
}
