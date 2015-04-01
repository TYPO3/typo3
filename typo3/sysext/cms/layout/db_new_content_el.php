<?php
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
 * New content elements wizard
 * (Part of the 'cms' extension)
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
unset($MCONF);
require __DIR__ . '/conf.php';
require $BACK_PATH . 'init.php';
// Unset MCONF/MLANG since all we wanted was back path etc. for this particular script.
unset($MCONF);
unset($MLANG);
// Merging locallang files/arrays:
$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_misc.xlf');
$LOCAL_LANG_orig = $LOCAL_LANG;
$LANG->includeLLFile('EXT:cms/layout/locallang_db_new_content_el.xlf');
\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($LOCAL_LANG_orig, $LOCAL_LANG);
$LOCAL_LANG = $LOCAL_LANG_orig;

\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
	'The new element class is moved to an own module. Please use BackendUtility::getModuleUrl(\'new_content_element\') to link to db_new_content_el.php. This script will be removed with version 8.'
);

$GLOBALS['SOBE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController::class);
$GLOBALS['SOBE']->init();
$GLOBALS['SOBE']->main();
$GLOBALS['SOBE']->printContent();
