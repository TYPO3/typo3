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

namespace TYPO3\CMS\Core\TypoScript\Tokenizer\Token;

/**
 * Each TokenInterface instance is a type of this Enum.
 *
 * @internal: Internal tokenizer structure.
 */
enum TokenType: int
{
    case T_NONE = 0; // tokenizer internal handling

    case T_IDENTIFIER = 100; // single word left of an operator. 'foo.bar' are two identifiers
    case T_VALUE = 200; // right side of an assignment, does not contain line breaks, also used as 'comment' body

    case T_OPERATOR_ASSIGNMENT = 300; // '='
    case T_OPERATOR_REFERENCE = 301; // '=<'
    case T_OPERATOR_COPY = 302; // '<'
    case T_OPERATOR_UNSET = 303; // '>'
    case T_OPERATOR_FUNCTION = 304; // ':='
    case T_OPERATOR_ASSIGNMENT_MULTILINE_START = 310; // '('
    case T_OPERATOR_ASSIGNMENT_MULTILINE_STOP = 311; // ')'

    case T_BLOCK_START = 400; // '{'
    case T_BLOCK_STOP = 401; // '}'

    case T_DOT = 500; // '.' identifier separator

    case T_BLANK = 600; // list of ' ' and "\t"

    case T_NEWLINE = 700; // "\n" or "\r\n"

    case T_COMMENT_ONELINE_HASH = 800; // '#...'
    case T_COMMENT_ONELINE_DOUBLESLASH = 801; // '//'
    case T_COMMENT_MULTILINE_START = 802; // '/*'
    case T_COMMENT_MULTILINE_STOP = 803; // '*/'

    case T_FUNCTION_NAME = 900; // 'addToList' and others
    case T_FUNCTION_VALUE_START = 901; // '(' after T_FUNCTION_NAME
    case T_FUNCTION_VALUE_STOP = 902; // ')' after T_FUNCTION_NAME

    case T_CONDITION_START = 1000; // '[' at start of line
    case T_CONDITION_STOP = 1001; // ']' after '[' in same line, body is a T_VALUE
    case T_CONDITION_ELSE = 1002; // 'ELSE' surrounded by '[' and ']'
    case T_CONDITION_END = 1003; // 'END' surrounded by '[' and ']'
    case T_CONDITION_GLOBAL = 1004; // 'GLOBAL' surrounded by '[' and ']'

    case T_CONSTANT = 1100; // '{$...}'

    case T_IMPORT_KEYWORD = 1200; // '@import'
    case T_IMPORT_START = 1201; // ''' (tick) or '"' (doubletick) after @import
    case T_IMPORT_STOP = 1202; // ''' (tick) or '"' (doubletick) after T_IMPORT_START

    case T_IMPORT_KEYWORD_OLD = 1300; // '<INCLUDE_TYPOSCRIPT:'
    case T_IMPORT_KEYWORD_OLD_STOP = 1301; // '>' after T_IMPORT_KEYWORD_OLD
}
