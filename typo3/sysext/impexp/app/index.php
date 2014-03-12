<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
