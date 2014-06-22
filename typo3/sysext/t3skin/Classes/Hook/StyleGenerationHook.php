<?php
namespace TYPO3\CMS\T3skin\Hook;

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
 * Hook for adding styles to backend page-generation in DocumentTemplate
 *
 * @author Stefan Neufeind <info [at] speedpartner.de>
 */
class StyleGenerationHook {

	/**
	 * Hooks into the \TYPO3\CMS\Backend\Template\DocumentTemplate::startPage and adds styles based on settings
	 * from color-schema from $GLOBALS['TBE_STYLES']
	 *
	 * @param array $hookParameters
	 * @param \TYPO3\CMS\Backend\Template\DocumentTemplate $documentTemplate Reference to instance of DocumentTemplate
	 * @return void
	 */
	public function preStartPageHook($hookParameters, \TYPO3\CMS\Backend\Template\DocumentTemplate $documentTemplate) {
		$documentTemplate->inDocStylesArray['TYPO3\\CMS\\T3skin\\Hook\\StyleGenerationHook'] = 'tr.typo3-CSM-itemRow:hover {background-color: ' . $GLOBALS['TBE_STYLES']['scriptIDindex']['typo3/alt_clickmenu.php']['mainColors']['bgColor5'] . ';} ';
	}

}