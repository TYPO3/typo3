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
 * Add vanilla TCA of parent page
 *
 * @todo: maybe not needed?
 */
class ParentPageTca implements FormDataProviderInterface
{
    /**
     * vanillaParentPageTca will stay NULL if record is added or edited below root node.
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        if (is_array($result['parentPageRow'])) {
            $result['vanillaParentPageTca'] = $GLOBALS['TCA']['pages'];
        }
        return $result;
    }
}
