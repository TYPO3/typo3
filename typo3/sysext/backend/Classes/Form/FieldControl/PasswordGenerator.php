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

namespace TYPO3\CMS\Backend\Form\FieldControl;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Renders a widget where a password can be generated, typically used with type=password
 *
 * @internal Only to be used by TYPO3. Might change in the future.
 */
class PasswordGenerator extends AbstractNode
{
    public function render(): array
    {
        $parameterArray = $this->data['parameterArray'];
        if (($parameterArray['fieldConf']['config']['type'] ?? '') !== 'password') {
            return [];
        }

        $options = $this->data['renderData']['fieldControlOptions'];
        $itemName = (string)$parameterArray['itemFormElName'];
        $id = StringUtility::getUniqueId('t3js-formengine-fieldcontrol-');

        // Handle options and fallback
        $title = $options['title'] ?? 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.generatePassword';

        $linkAttributes = [
            'id' => $id,
            'data-item-name' => $itemName,
        ];

        if ($options['allowEdit'] ?? true) {
            $linkAttributes['data-allow-edit'] = true;
        }

        if (is_array($options['passwordRules'] ?? false) && $options['passwordRules'] !== []) {
            $linkAttributes['data-password-rules'] = (string)json_encode($options['passwordRules'], JSON_THROW_ON_ERROR);
        }

        return [
            'iconIdentifier' => 'actions-refresh',
            'title' => $title,
            'linkAttributes' => $linkAttributes,
            'javaScriptModules' => [
                JavaScriptModuleInstruction::create('@typo3/backend/form-engine/field-control/password-generator.js')->instance($id),
            ],
        ];
    }
}
