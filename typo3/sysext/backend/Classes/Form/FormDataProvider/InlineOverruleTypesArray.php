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

/**
 * Add overrule types arrays for inline child records (FAL related)
 */
class InlineOverruleTypesArray implements FormDataProviderInterface
{

    /**
     * replace types definition for inline children if overruleTypesArray is defined
     *
     * @param array $result
     *
     * @return array
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        if (isset($result['inlineParentConfig']['foreign_types'])) {
            foreach ($result['inlineParentConfig']['foreign_types'] as $type => $config) {
                $result['processedTca']['types'][$type] = $config;
            }
        }

        return $result;
    }
}
