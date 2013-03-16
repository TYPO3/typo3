<?php
namespace TYPO3\CMS\Backend\Tree\Pagetree;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Susanne Moog <typo3@susanne-moog.de>
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
 * Class for pagetree indicator
 *
 * @author Susanne Moog <typo3@susanne-moog.de>
 */
class Indicator {

	/**
	 * Indicator Providers
	 *
	 * @var array
	 */
	protected $indicatorProviders = array();

	/**
	 * Constructor for class tx_reports_report_Status
	 */
	public function __construct() {
		$this->getIndicatorProviders();
	}

	/**
	 * Gets all registered indicator providers and instantiates them
	 */
	protected function getIndicatorProviders() {
		$providers = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['pagetree']['t3lib_tree_pagetree']['indicator']['providers'];
		if (!is_array($providers)) {
			return;
		}
		foreach ($providers as $indicatorProvider) {
			/** @var $indicatorProviderInstance \TYPO3\CMS\Backend\Tree\Pagetree\IndicatorProviderInterface */
			$indicatorProviderInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($indicatorProvider);
			if ($indicatorProviderInstance instanceof \TYPO3\CMS\Backend\Tree\Pagetree\IndicatorProviderInterface) {
				$this->indicatorProviders[] = $indicatorProviderInstance;
			}
		}
	}

	/**
	 * Runs through all indicator providers and returns all indicators collected.
	 *
	 * @return array An array of
	 */
	public function getAllIndicators() {
		$indicators = array();
		foreach ($this->indicatorProviders as $indicatorProvider) {
			$indicator = $indicatorProvider->getIndicator();
			if ($indicator) {
				$indicators[] = $indicator;
			}
		}
		return $indicators;
	}

}


?>