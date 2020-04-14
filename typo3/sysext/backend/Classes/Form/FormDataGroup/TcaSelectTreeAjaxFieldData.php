<?php

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

namespace TYPO3\CMS\Backend\Form\FormDataGroup;

use TYPO3\CMS\Backend\Form\FormDataGroupInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A data provider group dedicated for the type='select', renderType='selectTree'
 * calculate tree items FormSelectTreeAjaxController.
 */
class TcaSelectTreeAjaxFieldData implements FormDataGroupInterface
{
    /**
     * Compile TCA tree items
     *
     * @param array $result Initialized result array
     * @return array Result filled with data
     * @throws \UnexpectedValueException
     */
    public function compile(array $result)
    {
        $orderedProviderList = GeneralUtility::makeInstance(OrderedProviderList::class);
        $orderedProviderList->setProviderList(
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaSelectTreeAjaxFieldData']
        );

        return $orderedProviderList->compile($result);
    }
}
