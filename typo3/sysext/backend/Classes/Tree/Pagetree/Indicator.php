<?php
namespace TYPO3\CMS\Backend\Tree\Pagetree;

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
 * Class for pagetree indicator
 */
class Indicator
{
    /**
     * Indicator Providers
     *
     * @var array
     */
    protected $indicatorProviders = [];

    /**
     * Constructor for class tx_reports_report_Status
     */
    public function __construct()
    {
        $this->getIndicatorProviders();
    }

    /**
     * Gets all registered indicator providers and instantiates them
     */
    protected function getIndicatorProviders()
    {
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
    public function getAllIndicators()
    {
        $indicators = [];
        foreach ($this->indicatorProviders as $indicatorProvider) {
            $indicator = $indicatorProvider->getIndicator();
            if ($indicator) {
                $indicators[] = $indicator;
            }
        }
        return $indicators;
    }
}
