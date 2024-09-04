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

namespace TYPO3\CMS\Core\Site;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Set\SetCollector;

/**
 * @internal
 */
#[Autoconfigure(public: true)]
final readonly class TcaSiteSetCollector
{
    public function __construct(
        private SetCollector $setCollector,
    ) {}

    public function populateSiteSets(array &$fieldConfiguration): void
    {
        foreach ($this->setCollector->getSetDefinitions() as $set) {
            $fieldConfiguration['items'][] = [
                'label' => $this->getLanguageService()->sL($set->label),
                'value' => $set->name,
            ];
        }
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
