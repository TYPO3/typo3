<?php
/**
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
 * IMPORTING DATA:
 *
 * Incoming array has syntax:
 * GETvar 'id' = import page id (must be readable)
 *
 * file = 	(pointing to filename relative to PATH_site)
 *
 *
 *
 * [all relation fields are clear, but not files]
 * - page-tree is written first
 * - then remaining pages (to the root of import)
 * - then all other records are written either to related included pages or if not found to import-root (should be a sysFolder in most cases)
 * - then all internal relations are set and non-existing relations removed, relations to static tables preserved.
 *
 * EXPORTING DATA:
 *
 * Incoming array has syntax:
 *
 * file[] = file
 * dir[] = dir
 * list[] = table:pid
 * record[] = table:uid
 *
 * pagetree[id] = (single id)
 * pagetree[levels]=1,2,3, -1 = currently unpacked tree, -2 = only tables on page
 * pagetree[tables][]=table/_ALL
 *
 * external_ref[tables][]=table/_ALL
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */

$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Impexp\\Controller\\ImportExportController');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
