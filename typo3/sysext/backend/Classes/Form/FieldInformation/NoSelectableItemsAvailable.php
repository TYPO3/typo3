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

namespace TYPO3\CMS\Backend\Form\FieldInformation;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Renders an info badge indicating that no selectable items are available
 * for a relational field. Shown when backend debug mode is enabled.
 */
class NoSelectableItemsAvailable extends AbstractNode
{
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        $text = htmlspecialchars($this->getLanguageService()->sL(
            'core.core:labels.noSelectableItemsAvailable'
        ));
        $resultArray['html'] = '<div class="mb-2"><span class="badge badge-info text-wrap text-start lh-sm">' . $text . '</span></div>';
        return $resultArray;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
