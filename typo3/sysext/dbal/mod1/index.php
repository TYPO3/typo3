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
 * Module 'DBAL Debug' for the 'dbal' extension.
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author 	Karsten Dambekalns <karsten@typo3.org>
 */
$LANG->includeLLFile('EXT:dbal/mod1/locallang.xlf');
$BE_USER->modAccess($MCONF, 1);

$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Dbal\\Controller\\ModuleController');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
