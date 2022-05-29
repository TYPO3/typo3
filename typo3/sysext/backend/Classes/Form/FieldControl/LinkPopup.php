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
use TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeTrait;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Renders the icon with link parameters to open the element browser.
 * Used in InputLinkElement.
 */
class LinkPopup extends AbstractNode
{
    use OnFieldChangeTrait;

    /**
     * Link popup control
     *
     * @return array As defined by FieldControl class
     */
    public function render(): array
    {
        $options = $this->data['renderData']['fieldControlOptions'];

        $title = $options['title'] ?? 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.link';

        $parameterArray = $this->data['parameterArray'];
        $itemName = $parameterArray['itemFormElName'];

        $linkBrowserArguments = [];
        if (isset($options['blindLinkOptions'])) {
            $linkBrowserArguments['blindLinkOptions'] = $options['blindLinkOptions'];
        }
        if (isset($options['blindLinkFields'])) {
            $linkBrowserArguments['blindLinkFields'] = $options['blindLinkFields'];
        }
        if (isset($options['allowedExtensions'])) {
            $linkBrowserArguments['allowedExtensions'] = $options['allowedExtensions'];
        }
        $urlParameters = array_merge(
            [
                'params' => $linkBrowserArguments,
                'table' => $this->data['tableName'],
                'uid' => $this->data['databaseRow']['uid'],
                'pid' => $this->data['databaseRow']['pid'],
                'field' => $this->data['fieldName'],
                'formName' => 'editform',
                'itemName' => $itemName,
                'hmac' => GeneralUtility::hmac('editform' . $itemName, 'wizard_js'),
            ],
            $this->forwardOnFieldChangeQueryParams($parameterArray['fieldChangeFunc'] ?? [])
        );
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $url = (string)$uriBuilder->buildUriFromRoute('wizard_link', ['P' => $urlParameters]);
        $id = StringUtility::getUniqueId('t3js-formengine-fieldcontrol-');

        return [
            'iconIdentifier' => 'actions-wizard-link',
            'title' => $title,
            'linkAttributes' => [
                'id' => htmlspecialchars($id),
                'href' => $url,
                'data-item-name' => htmlspecialchars($itemName),
            ],
            'requireJsModules' => [
                JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Backend/FormEngine/FieldControl/LinkPopup')->instance('#' . $id),
            ],
        ];
    }
}
