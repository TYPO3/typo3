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
 * Module: Database integrity check
 *
 * This module lets you check if all pages and the records relate properly to each other
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor Jo Hasenau <info@cybercraft.de>
 */

$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Lowlevel\\View\\DatabaseIntegrityView');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
