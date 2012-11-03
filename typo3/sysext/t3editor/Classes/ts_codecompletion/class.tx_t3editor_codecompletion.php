<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Stephan Petzl <spetzl@gmx.at> and Christian Kartnig <office@hahnepeter.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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
require_once PATH_t3lib . 'class.t3lib_page.php';
$GLOBALS['LANG']->includeLLFile('EXT:t3editor/locallang.xml');
/*
 * @deprecated since 6.0, the classname tx_t3editor_codecompletion and this file is obsolete
 * and will be removed by 7.0. The class was renamed and is now located at:
 * typo3/sysext/t3editor/Classes/CodeCompletion.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('t3editor') . 'Classes/CodeCompletion.php';
?>