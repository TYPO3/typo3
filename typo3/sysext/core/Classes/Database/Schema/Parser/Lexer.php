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

/**
 * Scans a MySQL CREATE TABLE statement for tokens.
 */
class Lexer extends \Doctrine\Common\Lexer
{
    // All tokens that are not valid identifiers must be < 100
    const T_NONE = 1;
    const T_STRING = 2;
    const T_INPUT_PARAMETER = 3;
    const T_CLOSE_PARENTHESIS = 4;
    const T_OPEN_PARENTHESIS = 5;
    const T_COMMA = 6;
    const T_DIVIDE = 7;
    const T_DOT = 8;
    const T_EQUALS = 9;
    const T_GREATER_THAN = 10;
    const T_LOWER_THAN = 11;
    const T_MINUS = 12;
    const T_MULTIPLY = 13;
    const T_NEGATE = 14;
    const T_PLUS = 15;
    const T_OPEN_CURLY_BRACE = 16;
    const T_CLOSE_CURLY_BRACE = 17;
    const T_SEMICOLON = 18;

    // All tokens that are identifiers or keywords that could be considered as identifiers should be >= 100
    const T_IDENTIFIER = 100;

    // All tokens that could be considered as a data type should be >= 200
    const T_BIT = 201;
    const T_TINYINT = 202;
    const T_SMALLINT = 203;
    const T_MEDIUMINT = 204;
    const T_INT = 205;
    const T_INTEGER = 206;
    const T_BIGINT = 207;
    const T_REAL = 208;
    const T_DOUBLE = 209;
    const T_FLOAT = 210;
    const T_DECIMAL = 211;
    const T_NUMERIC = 212;
    const T_DATE = 213;
    const T_TIME = 214;
    const T_TIMESTAMP = 215;
    const T_DATETIME = 216;
    const T_YEAR = 217;
    const T_CHAR = 218;
    const T_VARCHAR = 219;
    const T_BINARY = 220;
    const T_VARBINARY = 221;
    const T_TINYBLOB = 222;
    const T_BLOB = 223;
    const T_MEDIUMBLOB = 224;
    const T_LONGBLOB = 225;
    const T_TINYTEXT = 226;
    const T_TEXT = 227;
    const T_MEDIUMTEXT = 228;
    const T_LONGTEXT = 229;
    const T_ENUM = 230;
    const T_SET = 231;
    const T_JSON = 232;

    // All keyword tokens should be >= 300
    const T_CREATE = 300;
    const T_TEMPORARY = 301;
    const T_TABLE = 302;
    const T_IF = 303;
    const T_NOT = 304;
    const T_EXISTS = 305;
    const T_CONSTRAINT = 306;
    const T_INDEX = 307;
    const T_KEY = 308;
    const T_FULLTEXT = 309;
    const T_SPATIAL = 310;
    const T_PRIMARY = 311;
    const T_UNIQUE = 312;
    const T_CHECK = 313;
    const T_DEFAULT = 314;
    const T_AUTO_INCREMENT = 315;
    const T_COMMENT = 316;
    const T_COLUMN_FORMAT = 317;
    const T_STORAGE = 318;
    const T_REFERENCES = 319;
    const T_NULL = 320;
    const T_FIXED = 321;
    const T_DYNAMIC = 322;
    const T_MEMORY = 323;
    const T_DISK = 324;
    const T_UNSIGNED = 325;
    const T_ZEROFILL = 326;
    const T_CURRENT_TIMESTAMP = 327;
    const T_CHARACTER = 328;
    const T_COLLATE = 329;
    const T_ASC = 330;
    const T_DESC = 331;
    const T_MATCH = 332;
    const T_FULL = 333;
    const T_PARTIAL = 334;
    const T_SIMPLE = 335;
    const T_ON = 336;
    const T_UPDATE = 337;
    const T_DELETE = 338;
    const T_RESTRICT = 339;
    const T_CASCADE = 340;
    const T_NO = 341;
    const T_ACTION = 342;
    const T_USING = 343;
    const T_BTREE = 344;
    const T_HASH = 345;
    const T_KEY_BLOCK_SIZE = 346;
    const T_WITH = 347;
    const T_PARSER = 348;
    const T_FOREIGN = 349;
    const T_ENGINE = 350;
    const T_AVG_ROW_LENGTH = 351;
    const T_CHECKSUM = 352;
    const T_COMPRESSION = 353;
    const T_CONNECTION = 354;
    const T_DATA= 355;
    const T_DIRECTORY = 356;
    const T_DELAY_KEY_WRITE = 357;
    const T_ENCRYPTION = 358;
    const T_INSERT_METHOD = 359;
    const T_MAX_ROWS = 360;
    const T_MIN_ROWS = 361;
    const T_PACK_KEYS = 362;
    const T_PASSWORD = 363;
    const T_ROW_FORMAT = 364;
    const T_STATS_AUTO_RECALC = 365;
    const T_STATS_PERSISTENT = 366;
    const T_STATS_SAMPLE_PAGES = 367;
    const T_TABLESPACE = 368;
    const T_UNION = 369;
    const T_PRECISION = 370;

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
     *
     * @return array
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
     *
     * @return array
     */
    protected function getNonCatchablePatterns(): array
    {
        return ['\s+'];
    }

    /**
     * Retrieve token type. Also processes the token value if necessary.
     *
     * @param string $value
     * @return int
     */
    protected function getType(&$value): int
    {
        $type = self::T_NONE;

        // Recognize numeric values
        if (is_numeric($value)) {
            if (strpos($value, '.') !== false || stripos($value, 'e') !== false) {
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
