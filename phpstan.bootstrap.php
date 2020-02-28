<?php

defined('LF') ?: define('LF', chr(10));
defined('CR') ?: define('CR', chr(13));
defined('CRLF') ?: define('CRLF', CR . LF);

define('FILE_DENY_PATTERN_DEFAULT', '\\.(php[3-8]?|phpsh|phtml|pht|phar|shtml|cgi)(\\..*)?$|\\.pl$|^\\.htaccess$');

define('TYPO3_REQUESTTYPE', 0);
define('TYPO3_REQUESTTYPE_FE', 1);
define('TYPO3_REQUESTTYPE_BE', 2);
define('TYPO3_REQUESTTYPE_CLI', 4);
define('TYPO3_REQUESTTYPE_AJAX', 8);
define('TYPO3_REQUESTTYPE_INSTALL', 16);

define('TYPO3_MODE', '');
define('TYPO3_mainDir', 'typo3/');
define('TYPO3_version', '');
define('TYPO3_branch', '');
