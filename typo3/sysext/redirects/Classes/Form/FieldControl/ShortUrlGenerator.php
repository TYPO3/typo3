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

namespace TYPO3\CMS\Redirects\Form\FieldControl;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Renders a widget to generate a random Short URL.
 *
 * This is typically used in combination with TCA renderType=shortUrl,
 * but can be potentially used with other field input types as well.
 *
 * @internal This is still a bit experimental and may change.
 */
class ShortUrlGenerator extends AbstractNode
{
    public function render(): array
    {
        $options = $this->data['renderData']['fieldControlOptions'];
        $itemName = (string)$this->data['parameterArray']['itemFormElName'];
        $id = StringUtility::getUniqueId('t3js-formengine-fieldcontrol-');

        // Handle options and fallback
        $title = $options['title'] ?? 'LLL:EXT:redirects/Resources/Private/Language/Modules/short_urls.xlf:generate_short_url';

        $linkAttributes = [
            'id' => $id,
            'data-item-name' => $itemName,
        ];

        return [
            'iconIdentifier' => 'actions-dice',
            'title' => $title,
            'linkAttributes' => $linkAttributes,
            'javaScriptModules' => [
                JavaScriptModuleInstruction::create('@typo3/redirects/short-url-generator.js')->instance($id),
            ],
        ];
    }
}
