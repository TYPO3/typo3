<?php
namespace TYPO3\CMS\T3skin\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Stefan Neufeind <info [at] speedpartner.de>
 *
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