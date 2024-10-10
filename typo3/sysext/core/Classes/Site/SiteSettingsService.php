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
use TYPO3\CMS\Core\Settings\SettingsTypeRegistry;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ArrayUtility;
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

    public function computeSettingsDiff(Site $site, array $rawSettings, bool $minify = true): array
    {
        $settings = [];
        $localSettings = [];

        $definitions = $this->getDefinitions($site);
        foreach ($rawSettings as $key => $value) {
            $definition = $definitions[$key] ?? null;
            if ($definition === null) {
                throw new \RuntimeException('Unexpected setting ' . $key . ' is not defined', 1724067004);
            }
            if ($definition->readonly) {
                continue;
            }
            $type = $this->settingsTypeRegistry->get($definition->type);
            $settings[$key] = $type->transformValue($value, $definition);
        }

        // Settings from sets – setting values without config/sites/*/settings.yaml applied
        $setSettings = $this->siteSettingsFactory->createSettings($site->getSets());
        // Settings from config/sites/*/settings.yaml only (our persistence target)
        $localSettings = $this->siteSettingsFactory->createSettingsForKeys(
            array_map(static fn(SettingDefinition $d) => $d->key, $definitions),
            $site->getIdentifier(),
            $site->getRawConfiguration()['settings'] ?? []
        );

        // Read existing settings, as we *must* not remove any settings that may be present because of
        //  * "undefined" settings that were supported since TYPO3 v12
        //  * (temporary) inactive sets
        $settingsTree = $localSettings->getAll();

        // Merge incoming settings into current settingsTree
        $changes = [];
        $deletions = [];
        foreach ($settings as $key => $value) {
            if ($minify && $value === $setSettings->get($key)) {
                if (ArrayUtility::isValidPath($settingsTree, $key, '.')) {
                    $settingsTree = $this->removeByPathWithAncestors($settingsTree, $key, '.');
                    $deletions[] = $key;
                }
                continue;
            }
            $settingsTree = ArrayUtility::setValueByPath($settingsTree, $key, $value, '.');
            $changes[] = $key;
        }
        return [
            'settings' => $settingsTree,
            'changes' => $changes,
            'deletions' => $deletions,
        ];
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

    private function getDefinitions(Site $site): array
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

    private function removeByPathWithAncestors(array $array, string $path, string $delimiter): array
    {
        if ($path === '') {
            return $array;
        }
        if (!ArrayUtility::isValidPath($array, $path, $delimiter)) {
            return $array;
        }

        $array = ArrayUtility::removeByPath($array, $path, $delimiter);
        $parts = explode($delimiter, $path);
        array_pop($parts);
        $parentPath = implode($delimiter, $parts);

        if ($parentPath !== '' && ArrayUtility::isValidPath($array, $parentPath, $delimiter)) {
            $parent = ArrayUtility::getValueByPath($array, $parentPath, $delimiter);
            if ($parent === []) {
                return $this->removeByPathWithAncestors($array, $parentPath, $delimiter);
            }
        }
        return $array;
    }
}
