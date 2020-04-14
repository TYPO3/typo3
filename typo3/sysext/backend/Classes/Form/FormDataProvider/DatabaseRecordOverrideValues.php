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

/**
 * Determine the final TCA type value
 */
class DatabaseRecordOverrideValues implements FormDataProviderInterface
{
    /**
     * Add override values to the databaseRow fields. As those values are not meant to
     * be overwritten by the user, the TCA of the field is set to type hidden.
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        foreach ($result['overrideValues'] as $fieldName => $fieldValue) {
            if (isset($result['processedTca']['columns'][$fieldName])) {
                $result['databaseRow'][$fieldName] = $fieldValue;
                $result['processedTca']['columns'][$fieldName]['config'] = [
                    'type' => 'hidden',
                    'renderType' => 'hidden',
                ];
            }
        }

        return $result;
    }
}
