<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('sys_note');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('sys_note', 'EXT:sys_note/Resources/Private/Language/locallang_csh_sysnote.xlf');
