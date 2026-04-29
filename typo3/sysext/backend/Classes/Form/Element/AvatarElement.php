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

use TYPO3\CMS\Backend\Backend\Avatar\DefaultAvatarProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders an avatar element for user settings.
 */
class AvatarElement extends AbstractFormElement
{
    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly UriBuilder $uriBuilder
    ) {}

    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        $fieldName = $this->data['fieldName'];
        $irreObjectId = '-0-be_users-avatar-' . $fieldName;
        $parameterArray = $this->data['parameterArray'];

        $defaultAvatarProvider = GeneralUtility::makeInstance(DefaultAvatarProvider::class);
        $avatarImage = $defaultAvatarProvider->getImage($this->data['databaseRow'], 32);

        $html = '';
        if ($avatarImage) {
            $icon = '<span class="avatar avatar-size-medium mb-2"><span class="avatar-image">'
                . '<img alt="" src="' . htmlspecialchars($avatarImage->getUrl()) . '"'
                . ' width="' . (int)$avatarImage->getWidth() . '"'
                . ' height="' . (int)$avatarImage->getHeight() . '"'
                . ' alt="" />'
                . '</span></span>';
            $html .= '<span id="image_' . htmlspecialchars($fieldName) . '">' . $icon . ' </span>';
        }

        $html .= '<input id="field_' . htmlspecialchars($fieldName) . '" type="hidden" '
            . 'name="' . htmlspecialchars($parameterArray['itemFormElName']) . '"'
            . ' value="' . (int)($this->data['databaseRow']['avatar'] ?? 0) . '" data-setup-avatar-field="' . htmlspecialchars($fieldName) . '" />';

        $html .= '<div class="form-group"><div class="form-group"><div class="form-control-wrap">';
        $html .= '<button type="button" id="add_button_' . htmlspecialchars($fieldName)
            . '" class="btn btn-default"'
            . ' title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/user_profile.xlf:avatar.open_file_browser')) . '"'
            . ' data-setup-avatar-url="' . htmlspecialchars((string)$this->uriBuilder->buildUriFromRoute('wizard_element_browser', ['mode' => 'file', 'allowedTypes' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] ?? '', 'irreObjectId' => $irreObjectId])) . '"'
            . '>' . $this->iconFactory->getIcon('actions-insert-record', IconSize::SMALL)->render()
            . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/user_profile.xlf:avatar.open_file_browser'))
            . '</button>';

        if ($avatarImage) {
            $html .= ' ';
            $html .= '<button type="button" id="clear_button_' . htmlspecialchars($fieldName)
                . '" class="btn btn-default"'
                . ' title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/user_profile.xlf:avatar.clear')) . '" '
                . '>' . $this->iconFactory->getIcon('actions-delete', IconSize::SMALL)->render()
                . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/user_profile.xlf:avatar.clear'))
                . '</button>';
        }
        $html .= '</div></div></div>';

        $resultArray['html'] = $html;

        return $resultArray;
    }
}
