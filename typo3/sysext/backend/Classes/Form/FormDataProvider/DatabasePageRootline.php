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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Set rootline
 */
class DatabasePageRootline implements FormDataProviderInterface
{
    /**
     * Fetch rootline
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $result['rootline'] = BackendUtility::BEgetRootLine($result['effectivePid'], '', true);
        return $result;
    }
}
