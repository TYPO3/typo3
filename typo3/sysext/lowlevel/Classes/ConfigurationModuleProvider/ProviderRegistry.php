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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Registry for configuration providers which is called by the ConfigurationProviderPass
 */
#[Autoconfigure(public: true)]
final class ProviderRegistry
{
    /**
     * Groups with a label provided by EXT:lowlevel, in the order they are shown in the module.
     * Any other value of the "group" tag attribute is used as label (LLL reference or plain text)
     * of a custom group, shown after these. Providers without "group" end up in DEFAULT_GROUP, shown last.
     */
    public const GROUPS = ['core', 'data', 'backend', 'sites', 'requestHandling', 'events', 'frameworks'];
    public const DEFAULT_GROUP = 'extensions';

    /**
     * @var ProviderInterface[]
     */
    private array $providers = [];

    /**
     * @var array<string, string>
     */
    private array $groups = [];

    public function registerProvider(ProviderInterface $provider, array $attributes): void
    {
        $this->providers[$attributes['identifier']] = $provider($attributes);
        $group = (string)($attributes['group'] ?? '');
        $this->groups[$attributes['identifier']] = $group !== '' ? $group : self::DEFAULT_GROUP;
    }

    public function hasProvider(string $identifier): bool
    {
        return isset($this->providers[$identifier]);
    }

    public function getProvider(string $identifier): ProviderInterface
    {
        if (!isset($this->providers[$identifier])) {
            throw new \InvalidArgumentException('No provider for identifier ' . $identifier . 'found.', 1606490398);
        }

        return $this->providers[$identifier];
    }

    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Providers per group: the groups of self::GROUPS, custom groups in order of their
     * first provider, and self::DEFAULT_GROUP last. Empty groups are left out.
     * The order of providers within a group is the order of getProviders().
     *
     * @return array<string, ProviderInterface[]>
     */
    public function getGroupedProviders(): array
    {
        $known = array_fill_keys(self::GROUPS, []);
        $custom = [];
        $default = [];
        foreach ($this->providers as $identifier => $provider) {
            $group = $this->groups[$identifier];
            if ($group === self::DEFAULT_GROUP) {
                $default[$identifier] = $provider;
            } elseif (isset($known[$group])) {
                $known[$group][$identifier] = $provider;
            } else {
                $custom[$group][$identifier] = $provider;
            }
        }
        return array_filter($known + $custom + [self::DEFAULT_GROUP => $default]);
    }

    /**
     * Label of a group as returned by getGroupedProviders(). Known groups use the labels of
     * EXT:lowlevel, custom groups starting with "LLL:" are translated, any other value is
     * plain text. If no label can be resolved, the group itself is returned.
     */
    public function getGroupLabel(string $group, LanguageService $languageService): string
    {
        if ($group === self::DEFAULT_GROUP || in_array($group, self::GROUPS, true)) {
            $label = (string)$languageService->translate('group.' . $group, 'lowlevel.messages');
        } elseif (str_starts_with($group, 'LLL:')) {
            $label = $languageService->sL($group);
        } else {
            $label = $group;
        }
        return $label !== '' ? $label : $group;
    }

    public function getFirstProvider(): ?ProviderInterface
    {
        return $this->providers !== [] ? reset($this->providers) : null;
    }
}
