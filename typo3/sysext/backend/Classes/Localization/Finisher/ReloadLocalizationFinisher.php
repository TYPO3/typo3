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

namespace TYPO3\CMS\Backend\Localization\Finisher;

use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Finisher to reload the current page/content frame after localization
 *
 * @internal
 */
final readonly class ReloadLocalizationFinisher implements LocalizationFinisherInterface
{
    public function getIdentifier(): string
    {
        return 'reload';
    }

    public function getModule(): string
    {
        return '@typo3/backend/wizard/finisher/reload-finisher.js';
    }

    public function getData(): array
    {
        return [];
    }

    public function getLabels(): array
    {
        return [
            'successTitle' => $this->getLanguageService()->sL('backend.wizards.localization:localization_wizard.finisher.reload.success.title'),
            'successDescription' => $this->getLanguageService()->sL('backend.wizards.localization:localization_wizard.finisher.reload.success.description'),
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'identifier' => $this->getIdentifier(),
            'module' => $this->getModule(),
            'data' => $this->getData(),
            'labels' => $this->getLabels(),
        ];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
