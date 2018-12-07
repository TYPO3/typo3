<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Backend\Form\FieldInformation;

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

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Render localized ['columns']['theField']['description'] text as default
 * field information node. This is typically displayed in elements below the
 * element label and the field content.
 */
class TcaDescription extends AbstractNode
{
    /**
     * Handler for single nodes
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        if (!empty($this->data['parameterArray']['fieldConf']['description'])) {
            $fieldInformationText = $this->getLanguageService()->sL($this->data['parameterArray']['fieldConf']['description']);
            if (trim($fieldInformationText) !== '') {
                $resultArray['html'] = '<span class="formengine-field-item-description text-muted">' . htmlspecialchars($fieldInformationText) . '</span>';
            }
        }
        return $resultArray;
    }

    /**
     * Returns the LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
