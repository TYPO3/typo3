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

namespace TYPO3\CMS\Extbase\Configuration;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Extbase\Configuration\Exception\NoServerRequestGivenException;

/**
 * Generic ConfigurationManager implementation. Uses BackendConfigurationManager
 * or FrontendConfigurationManager depending on request type.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class ConfigurationManager implements ConfigurationManagerInterface
{
    private ?ServerRequestInterface $request = null;
    private array $configuration = [];

    /**
     * @todo Use runtime cache
     */
    private array $feConfigCache = [];

    public function __construct(
        private readonly FrontendConfigurationManager $feConfigManager,
        private readonly BackendConfigurationManager $beConfigManager,
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function setConfiguration(array $configuration = []): void
    {
        $this->configuration = $configuration;
        $this->feConfigCache = [];
    }

    /**
     * Returns the specified configuration.
     * The actual configuration will be merged from different sources in a defined order.
     *
     * You can get the following types of configuration invoking:
     * CONFIGURATION_TYPE_SETTINGS: Extbase settings
     * CONFIGURATION_TYPE_FRAMEWORK: the current module/plugin settings
     * CONFIGURATION_TYPE_FULL_TYPOSCRIPT: a raw TS array
     *
     * Note that this is a low level method and only makes sense to be used by Extbase internally.
     *
     * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
     * @param string|null $extensionName if specified, the configuration for the given extension will be returned.
     * @param string|null $pluginName if specified, the configuration for the given plugin will be returned.
     * @return array The configuration
     */
    public function getConfiguration(string $configurationType, ?string $extensionName = null, ?string $pluginName = null): array
    {
        $request = $this->request;
        $configuration = $this->configuration;
        if ($request === null && ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            // @todo: deprecate
            $request = $GLOBALS['TYPO3_REQUEST'];
        }
        if ($request === null) {
            // This is a *specific* exception (as opposed to a global one) to allow consumers to opt-out
            // of a Request dependency. The extbase persistence layer is an example: It can be useful to
            // only have a loose Request / TypoScript dependency in it, and the TS config values / toggles
            // used within the persistence layer are not crucially important and can fall back to hard
            // coded defaults.
            // Note custom extensions should typically not catch this exception. The dependency to
            // the current request is still an important dependency in most extbase places, e.g. in
            // controller and view related code.
            throw new NoServerRequestGivenException('No request given. ConfigurationManager has not been initialized properly.', 1721920500);
        }
        if (ApplicationType::fromRequest($request)->isFrontend()) {
            if ($configurationType === self::CONFIGURATION_TYPE_FULL_TYPOSCRIPT) {
                return $this->feConfigManager->getTypoScriptSetup($request);
            }
            // @todo Throw if empty to not end up with '_': Invalid setup/call!
            $feConfigCacheKey = strtolower(
                ($extensionName ?? $configuration['extensionName'] ?? null)
                . '_'
                . ($pluginName ?? $configuration['pluginName'] ?? null)
            );
            if ($configurationType === self::CONFIGURATION_TYPE_SETTINGS) {
                if (isset($this->feConfigCache[$feConfigCacheKey])) {
                    return $this->feConfigCache[$feConfigCacheKey]['settings'] ?? [];
                }
                $this->feConfigCache[$feConfigCacheKey] = $this->feConfigManager->getConfiguration($request, $this->configuration, $extensionName, $pluginName);
                return $this->feConfigCache[$feConfigCacheKey]['settings'] ?? [];
            }
            if ($configurationType === self::CONFIGURATION_TYPE_FRAMEWORK) {
                if (isset($this->feConfigCache[$feConfigCacheKey])) {
                    return $this->feConfigCache[$feConfigCacheKey];
                }
                $this->feConfigCache[$feConfigCacheKey] = $this->feConfigManager->getConfiguration($request, $this->configuration, $extensionName, $pluginName);
                return $this->feConfigCache[$feConfigCacheKey];
            }
            throw new \RuntimeException('Invalid configuration type "' . $configurationType . '"', 1206031879);
        } else {
            return match ($configurationType) {
                self::CONFIGURATION_TYPE_SETTINGS => $this->beConfigManager->getConfiguration($request, $this->configuration, $extensionName, $pluginName)['settings'] ?? [],
                self::CONFIGURATION_TYPE_FRAMEWORK => $this->beConfigManager->getConfiguration($request, $this->configuration, $extensionName, $pluginName),
                self::CONFIGURATION_TYPE_FULL_TYPOSCRIPT => $this->beConfigManager->getTypoScriptSetup($request),
                default => throw new \RuntimeException('Invalid configuration type "' . $configurationType . '"', 1721928055),
            };
        }
    }
}
