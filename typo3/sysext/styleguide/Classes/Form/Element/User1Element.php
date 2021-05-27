<?php

declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\Form\Element;

/**
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

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;

/**
 * A user function rendering a type=user TCA type used in user_1
 */
class User1Element extends AbstractFormElement
{
    /**
     * @return array<string> As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $result = $this->initializeResultArray();
        $parameters = $this->data['parameterArray'];
        $html = [];
        $html[] = '<div style="border: 1px dashed ' . htmlspecialchars($parameters['fieldConf']['config']['parameters']['color'] ?? '') . '" >';
        $html[] = '<h2>Own form field using a parameter</h2>';
        $html[] = '<input'
            . ' type="input"'
            . ' name="' . htmlspecialchars($parameters['itemFormElName']) . '"'
            . ' value="' . htmlspecialchars($parameters['itemFormElValue']) . '"'
            . ' onchange="' . htmlspecialchars(implode('', $parameters['fieldChangeFunc'])) . '"'
            . ' />';
        $html[] = '</div>';
        $result['html'] = implode(chr(10), $html);
        return $result;
    }
}
