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
 * Provides field information texts for form engine fields concerning site configuration module
 */
class SiteConfiguration extends AbstractNode
{
    /**
     * Handler for single nodes
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $resultArray = $this->initializeResultArray();
        $fieldInformationText = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:' . $this->data['tableName'] . '.' . $this->data['fieldName']);
        if ($fieldInformationText !== $this->data['fieldName']) {
            $resultArray['html'] = $fieldInformationText;
        }
        return $resultArray;
    }

    /**
     * Returns the LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
