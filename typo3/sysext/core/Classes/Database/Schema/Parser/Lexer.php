<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Database\Schema\Parser;

use Doctrine\Common\Lexer\AbstractLexer;

/**
 * Scans a MySQL CREATE TABLE statement for tokens.
 */
class Lexer extends AbstractLexer
{
    // All tokens that are not valid identifiers must be < 100
    public const T_NONE = 1;
    public const T_STRING = 2;
    public const T_INPUT_PARAMETER = 3;
    public const T_CLOSE_PARENTHESIS = 4;
    public const T_OPEN_PARENTHESIS = 5;
    public const T_COMMA = 6;
    public const T_DIVIDE = 7;
    public const T_DOT = 8;
    public const T_EQUALS = 9;
    public const T_GREATER_THAN = 10;
    public const T_LOWER_THAN = 11;
    public const T_MINUS = 12;
    public const T_MULTIPLY = 13;
    public const T_NEGATE = 14;
    public const T_PLUS = 15;
    public const T_OPEN_CURLY_BRACE = 16;
    public const T_CLOSE_CURLY_BRACE = 17;
    public const T_SEMICOLON = 18;

    // All tokens that are identifiers or keywords that could be considered as identifiers should be >= 100
    public const T_IDENTIFIER = 100;

    // All tokens that could be considered as a data type should be >= 200
    public const T_BIT = 201;
    public const T_TINYINT = 202;
    public const T_SMALLINT = 203;
    public const T_MEDIUMINT = 204;
    public const T_INT = 205;
    public const T_INTEGER = 206;
    public const T_BIGINT = 207;
    public const T_REAL = 208;
    public const T_DOUBLE = 209;
    public const T_FLOAT = 210;
    public const T_DECIMAL = 211;
    public const T_NUMERIC = 212;
    public const T_DATE = 213;
    public const T_TIME = 214;
    public const T_TIMESTAMP = 215;
    public const T_DATETIME = 216;
    public const T_YEAR = 217;
    public const T_CHAR = 218;
    public const T_VARCHAR = 219;
    public const T_BINARY = 220;
    public const T_VARBINARY = 221;
    public const T_TINYBLOB = 222;
    public const T_BLOB = 223;
    public const T_MEDIUMBLOB = 224;
    public const T_LONGBLOB = 225;
    public const T_TINYTEXT = 226;
    public const T_TEXT = 227;
    public const T_MEDIUMTEXT = 228;
    public const T_LONGTEXT = 229;
    public const T_ENUM = 230;
    public const T_SET = 231;
    public const T_JSON = 232;

    // All keyword tokens should be >= 300
    public const T_CREATE = 300;
    public const T_TEMPORARY = 301;
    public const T_TABLE = 302;
    public const T_IF = 303;
    public const T_NOT = 304;
    public const T_EXISTS = 305;
    public const T_CONSTRAINT = 306;
    public const T_INDEX = 307;
    public const T_KEY = 308;
    public const T_FULLTEXT = 309;
    public const T_SPATIAL = 310;
    public const T_PRIMARY = 311;
    public const T_UNIQUE = 312;
    public const T_CHECK = 313;
    public const T_DEFAULT = 314;
    public const T_AUTO_INCREMENT = 315;
    public const T_COMMENT = 316;
    public const T_COLUMN_FORMAT = 317;
    public const T_STORAGE = 318;
    public const T_REFERENCES = 319;
    public const T_NULL = 320;
    public const T_FIXED = 321;
    public const T_DYNAMIC = 322;
    public const T_MEMORY = 323;
    public const T_DISK = 324;
    public const T_UNSIGNED = 325;
    public const T_ZEROFILL = 326;
    public const T_CURRENT_TIMESTAMP = 327;
    public const T_CHARACTER = 328;
    public const T_COLLATE = 329;
    public const T_ASC = 330;
    public const T_DESC = 331;
    public const T_MATCH = 332;
    public const T_FULL = 333;
    public const T_PARTIAL = 334;
    public const T_SIMPLE = 335;
    public const T_ON = 336;
    public const T_UPDATE = 337;
    public const T_DELETE = 338;
    public const T_RESTRICT = 339;
    public const T_CASCADE = 340;
    public const T_NO = 341;
    public const T_ACTION = 342;
    public const T_USING = 343;
    public const T_BTREE = 344;
    public const T_HASH = 345;
    public const T_KEY_BLOCK_SIZE = 346;
    public const T_WITH = 347;
    public const T_PARSER = 348;
    public const T_FOREIGN = 349;
    public const T_ENGINE = 350;
    public const T_AVG_ROW_LENGTH = 351;
    public const T_CHECKSUM = 352;
    public const T_COMPRESSION = 353;
    public const T_CONNECTION = 354;
    public const T_DATA = 355;
    public const T_DIRECTORY = 356;
    public const T_DELAY_KEY_WRITE = 357;
    public const T_ENCRYPTION = 358;
    public const T_INSERT_METHOD = 359;
    public const T_MAX_ROWS = 360;
    public const T_MIN_ROWS = 361;
    public const T_PACK_KEYS = 362;
    public const T_PASSWORD = 363;
    public const T_ROW_FORMAT = 364;
    public const T_STATS_AUTO_RECALC = 365;
    public const T_STATS_PERSISTENT = 366;
    public const T_STATS_SAMPLE_PAGES = 367;
    public const T_TABLESPACE = 368;
    public const T_UNION = 369;
    public const T_PRECISION = 370;

    /**
     * Creates a new statement scanner object.
     *
     * @param string $input A statement string.
     */
    public function __construct($input)
    {
        $this->setInput($input);
    }

    /**
     * Lexical catchable patterns.
     */
    protected function getCatchablePatterns(): array
    {
        return [
            '(?:-?[0-9]+(?:[\.][0-9]+)*)(?:e[+-]?[0-9]+)?', // numbers
            '`(?:[^`]|``)*`', // quoted identifiers
            "'(?:[^']|'')*'", // quoted strings
            '\)', // closing parenthesis
            '[a-z0-9$_][\w$]*', // unquoted identifiers
        ];
    }

    /**
     * Lexical non-catchable patterns.
     */
    protected function getNonCatchablePatterns(): array
    {
        return ['\s+'];
    }

    /**
     * Retrieve token type. Also processes the token value if necessary.
     *
     * @param string $value
     */
    protected function getType(&$value): int
    {
        $type = self::T_NONE;

        // Recognize numeric values
        if (is_numeric($value)) {
            if (str_contains($value, '.') || stripos($value, 'e') !== false) {
                return self::T_FLOAT;
            }

            return self::T_INTEGER;
        }

        // Recognize quoted strings
        if ($value[0] === "'") {
            $value = str_replace("''", "'", substr($value, 1, -1));

            return self::T_STRING;
        }

        // Recognize quoted strings
        if ($value[0] === '`') {
            $value = str_replace('``', '`', substr($value, 1, -1));

            return self::T_IDENTIFIER;
        }

        // Recognize identifiers, aliased or qualified names
        if (ctype_alpha($value[0])) {
            $name = 'TYPO3\\CMS\\Core\\Database\\Schema\\Parser\\Lexer::T_' . strtoupper($value);

            if (defined($name)) {
                $type = constant($name);

                if ($type > 100) {
                    return $type;
                }
            }

            return self::T_STRING;
        }

        switch ($value) {
            // Recognize symbols
            case '.':
                return self::T_DOT;
            case ';':
                return self::T_SEMICOLON;
            case ',':
                return self::T_COMMA;
            case '(':
                return self::T_OPEN_PARENTHESIS;
            case ')':
                return self::T_CLOSE_PARENTHESIS;
            case '=':
                return self::T_EQUALS;
            case '>':
                return self::T_GREATER_THAN;
            case '<':
                return self::T_LOWER_THAN;
            case '+':
                return self::T_PLUS;
            case '-':
                return self::T_MINUS;
            case '*':
                return self::T_MULTIPLY;
            case '/':
                return self::T_DIVIDE;
            case '!':
                return self::T_NEGATE;
            case '{':
                return self::T_OPEN_CURLY_BRACE;
            case '}':
                return self::T_CLOSE_CURLY_BRACE;
                // Default
            default:
                // Do nothing
        }

        return $type;
    }
}
