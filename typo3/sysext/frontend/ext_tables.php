<?php
defined('TYPO3_MODE') or die();

// Add allowed records to pages
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tt_content,sys_template,sys_domain,backend_layout');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_layout', 'EXT:frontend/Resources/Private/Language/locallang_csh_weblayout.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('fe_groups', 'EXT:frontend/Resources/Private/Language/locallang_csh_fe_groups.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('fe_users', 'EXT:frontend/Resources/Private/Language/locallang_csh_fe_users.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('sys_domain', 'EXT:frontend/Resources/Private/Language/locallang_csh_sysdomain.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('sys_template', 'EXT:frontend/Resources/Private/Language/locallang_csh_systmpl.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tt_content', 'EXT:frontend/Resources/Private/Language/locallang_csh_ttcontent.xlf');
