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
 * Displays image selector for the RTE
 *
 * @author 	Kasper Skårhøj <kasper@typo3.com>
 * @author 	Stanislas Rolland <typo3(arobas)jbr.ca>
 */

// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Rtehtmlarea\\Controller\\SelectImageController');
$SOBE->main();
$SOBE->printContent();
