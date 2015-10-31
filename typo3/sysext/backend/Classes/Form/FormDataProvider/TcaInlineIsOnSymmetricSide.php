<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Determine the wether the child is on symmetric side or not.
 *
 * TCA ctrl fields like label and label_alt are evaluated and their
 * current values from databaseRow used to create the title.
 */
class TcaInlineIsOnSymmetricSide implements FormDataProviderInterface
{
    /**
     * Enrich the processed record information with the resolved title
     *
     * @param array $result Incoming result array
     * @return array Modified array
     */
    public function addData(array $result)
    {
        if (!$result['isInlineChild']) {
            return $result;
        }

        $result['isOnSymmetricSide'] = MathUtility::canBeInterpretedAsInteger($result['databaseRow']['uid'])
            && $result['inlineParentConfig']['symmetric_field']
            // non-strict comparison by intention
            && $result['inlineParentUid'] == $result['databaseRow'][$result['inlineParentConfig']['symmetric_field']][0];

        return $result;
    }
}
