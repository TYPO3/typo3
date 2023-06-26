<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Form\Element;

/**
 * Generation of elements of the type "user". This is a dummy implementation.
 *
 * type="user" elements should be combined with a custom renderType to create custom output.
 * This implementation registered for type="user" kicks in if no renderType is given and is just
 * a fallback implementation to hint developers that the TCA registration is incomplete.
 */
class UserElement extends AbstractFormElement
{
    /**
     * User defined field type
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        // Render some dummy output to explain this element should usually not be called at all.
        $resultArray = $this->initializeResultArray();
        // @deprecated since v12, will be removed with v13 when all elements handle label/legend on their own
        $resultArray['labelHasBeenHandled'] = true;
        $fieldName = $this->data['flexFormFieldName'] ?? $this->data['fieldName'];
        $html = [];
        $html[] = '<div class="alert alert-warning">';
        $html[] = 'This is dummy output: Field <code>' . htmlspecialchars($fieldName) . '</code>';
        $html[] = 'of table <code>' . htmlspecialchars($this->data['tableName']) . '</code>';
        $html[] = ' is registered as type="user" element without a specific renderType.';
        $html[] = ' Please look up details in TCA reference documentation for type="user".';
        $html[] = '</div>';
        $resultArray['html'] = $this->wrapWithFieldsetAndLegend(implode(LF, $html));
        return $resultArray;
    }
}
