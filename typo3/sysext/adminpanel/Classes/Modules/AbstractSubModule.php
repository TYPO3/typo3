<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Modules;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Abstract SubModule - Base class for sub modules in the admin panel
 *
 * Extend this class when writing own sub modules
 */
abstract class AbstractSubModule implements AdminPanelSubModuleInterface
{
    /**
     * @inheritdoc
     */
    public function getSettings(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function initializeModule(ServerRequestInterface $request): void
    {
    }

    /**
     * @inheritdoc
     */
    public function onSubmit(array $configurationToSave, ServerRequestInterface $request): void
    {
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
