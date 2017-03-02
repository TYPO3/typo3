<?php
declare(strict_types=1);
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
 * Override cropping configuration of child (mostly sys_file_reference) TCA
 */
class TcaOverrideChildCroppingConfiguration extends AbstractItemProvider implements FormDataProviderInterface
{
    /**
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        if (isset($result['inlineParentConfig']['overrideCropVariants'])
            && is_array($result['inlineParentConfig']['overrideCropVariants'])) {
            foreach ($result['inlineParentConfig']['overrideCropVariants'] as $fieldName => $cropVariantsConfig) {
                if (
                    isset($result['processedTca']['columns'][$fieldName]['config']['type'])
                    && $result['processedTca']['columns'][$fieldName]['config']['type'] === 'imageManipulation'
                ) {
                    $result['processedTca']['columns'][$fieldName]['config']['cropVariants'] = $cropVariantsConfig;
                }
            }
        }
        return $result;
    }
}
