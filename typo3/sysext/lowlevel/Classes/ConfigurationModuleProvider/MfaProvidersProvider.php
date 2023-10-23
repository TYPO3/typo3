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

namespace TYPO3\CMS\Lowlevel\ConfigurationModuleProvider;

use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;

class MfaProvidersProvider extends AbstractProvider
{
    public function __construct(protected MfaProviderRegistry $mfaProviderRegistry) {}

    public function getConfiguration(): array
    {
        $providers = $this->mfaProviderRegistry->getProviders();
        $configuration = [];
        $languageService = $this->getLanguageService();
        foreach ($providers as $identifier => $provider) {
            $title = $languageService->sL($provider->getTitle());
            if ($title !== $provider->getTitle()) {
                $title .= ' [' . $provider->getTitle() . ']';
            }
            $description = $languageService->sL($provider->getDescription());
            if ($description !== $provider->getDescription()) {
                $description .= ' [' . $provider->getDescription() . ']';
            }
            $configuration[$identifier] = [
                'title' => $title,
                'description' => $description,
                'isDefaultAllowed' => $provider->isDefaultProviderAllowed(),
            ];
        }
        return $configuration;
    }
}
