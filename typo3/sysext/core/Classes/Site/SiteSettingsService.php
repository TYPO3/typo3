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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingsTree;
use TYPO3\CMS\Core\Settings\SettingsTypeRegistry;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
readonly class SiteSettingsService
{
    public function __construct(
        protected SiteWriter $siteWriter,
        #[Autowire(service: 'cache.core')]
        protected PhpFrontend $codeCache,
        protected SetRegistry $setRegistry,
        protected SiteSettingsFactory $siteSettingsFactory,
        protected SettingsTypeRegistry $settingsTypeRegistry,
        protected FlashMessageService $flashMessageService,
    ) {}

    public function hasSettingsDefinitions(Site $site): bool
    {
        return count($this->getDefinitions($site)) > 0;
    }

    public function getUncachedSettings(Site $site): SiteSettings
    {
        // create a fresh Settings instance instead of using
        // $site->getSettings() which may have been loaded from cache
        return $this->siteSettingsFactory->createSettings(
            $site->getSets(),
            $site->getIdentifier(),
            $site->getRawConfiguration()['settings'] ?? [],
        );
    }

    public function getSetSettings(Site $site): SiteSettings
    {
        return $this->siteSettingsFactory->createSettings($site->getSets());
    }

    public function getLocalSettings(Site $site): SiteSettings
    {
        $definitions = $this->getDefinitions($site);
        return $this->siteSettingsFactory->createSettingsForKeys(
            array_map(static fn(SettingDefinition $d) => $d->key, $definitions),
            $site->getIdentifier(),
            $site->getRawConfiguration()['settings'] ?? []
        );
    }

    public function computeSettingsDiff(Site $site, SiteSettings $newSettings, bool $minify = true): SettingsTree
    {
        // Settings from sets only – setting values without site-local config/sites/*/settings.yaml applied
        $defaultSettings = $minify ? $this->siteSettingsFactory->createSettings($site->getSets(), null) : null;

        // Settings from config/sites/*/settings.yaml only (our persistence target)
        $localSettings = $this->siteSettingsFactory->loadLocalSettingsTree($site->getIdentifier()) ??
            $site->getRawConfiguration()['settings'] ?? [];

        return SettingsTree::diff(
            $localSettings,
            $newSettings,
            $defaultSettings,
        );
    }

    public function writeSettings(Site $site, array $settings): void
    {
        try {
            $this->siteWriter->writeSettings($site->getIdentifier(), $settings);
        } catch (SiteConfigurationWriteException $e) {
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $e->getMessage(), '', ContextualFeedbackSeverity::ERROR, true);
            $defaultFlashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        // SiteWriter currently does not invalidate the code cache, see #103804
        $this->codeCache->flush();
    }

    /**
     * @return array<string, SettingDefinition>
     */
    public function getDefinitions(Site $site): array
    {
        $sets = $this->setRegistry->getSets(...$site->getSets());
        $definitions = [];
        foreach ($sets as $set) {
            foreach ($set->settingsDefinitions as $settingDefinition) {
                $definitions[$settingDefinition->key] = $settingDefinition;
            }
        }
        return $definitions;
    }

    public function createSettingsFromFormData(Site $site, array $settingsMap): SiteSettings
    {
        return $this->siteSettingsFactory->createSettingsFromFormData($settingsMap, $this->getDefinitions($site));
    }
}
