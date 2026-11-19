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

namespace TYPO3Tests\TestCanonicalPlugin;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Attribute\AsAllowedCallable;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;

#[Autoconfigure(public: true)]
final readonly class CanonicalPluginController
{
    public function __construct(
        private MetaTagManagerRegistry $metaTagManagerRegistry,
    ) {}

    #[AsAllowedCallable]
    public function setCanonical(string $content, array $configuration): string
    {
        $manager = $this->metaTagManagerRegistry->getManagerForProperty('canonical');
        if (($configuration['canonical'] ?? '') === '') {
            $manager->removeProperty('canonical');
        } else {
            $manager->addProperty('canonical', $configuration['canonical'], [], true);
        }
        return 'canonical plugin';
    }
}
