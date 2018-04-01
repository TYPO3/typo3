<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Form\FormDataGroup;

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

use TYPO3\CMS\Backend\Form\FormDataGroupInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A data provider group for site and language configuration.
 *
 * This data group is for data fetched from sites yml files,
 * it is fed by "fake TCA" since there are no real db records.
 *
 * It's similar to "fullDatabaseRecord", with some unused TCA types
 * kicked out and some own data providers for record data and inline handling.
 */
class SiteConfigurationDataGroup implements FormDataGroupInterface
{
    /**
     * Compile form data
     *
     * @param array $result Initialized result array
     * @return array Result filled with data
     * @throws \UnexpectedValueException
     */
    public function compile(array $result)
    {
        $orderedProviderList = GeneralUtility::makeInstance(OrderedProviderList::class);
        $orderedProviderList->setProviderList(
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['siteConfiguration']
        );
        return $orderedProviderList->compile($result);
    }
}
