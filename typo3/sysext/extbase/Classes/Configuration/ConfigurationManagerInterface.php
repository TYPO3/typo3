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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class ConfigurationManagerInterface
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
interface ConfigurationManagerInterface extends SingletonInterface
{
    public const CONFIGURATION_TYPE_FRAMEWORK = 'Framework';
    public const CONFIGURATION_TYPE_SETTINGS = 'Settings';
    public const CONFIGURATION_TYPE_FULL_TYPOSCRIPT = 'FullTypoScript';

    /**
     * @deprecated since v12. Remove in v13.
     */
    public function setContentObject(ContentObjectRenderer $contentObject): void;

    /**
     * @deprecated since v12. Remove in v13.
     */
    public function getContentObject(): ?ContentObjectRenderer;

    /**
     * Returns the specified configuration.
     * The actual configuration will be merged from different sources in a defined order.
     *
     * Note that this is a low level method and only makes sense to be used by Extbase internally.
     *
     * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
     * @param string|null $extensionName if specified, the configuration for the given extension will be returned.
     * @param string|null $pluginName if specified, the configuration for the given plugin will be returned.
     * @return array The configuration
     */
    public function getConfiguration(string $configurationType, ?string $extensionName = null, ?string $pluginName = null): array;

    /**
     * Sets the specified raw configuration coming from the outside.
     * Note that this is a low level method and only makes sense to be used by Extbase internally.
     *
     * @param array $configuration The new configuration
     * @internal Set by extbase bootstrap internally.
     * @todo: It may be possible to remove this in v13?!
     */
    public function setConfiguration(array $configuration = []): void;

    /**
     * Set the current request. The ConfigurationManager needs this to
     * determine which concrete ConfigurationManager (BE / FE) has to be
     * created, and the concrete ConfigurationManager need this to
     * access current site and similar.
     *
     * This state is updated by extbase bootstrap.
     *
     * Note this makes this singleton stateful! This is ugly, but can't
     * be avoided since the ConfigurationManager is injected into services
     * that are injected itself. This stateful singleton is of course an
     * anti-pattern, but it is very hard to get rid of until a re-design
     * of the extbase configuration logic.
     *
     * @param ServerRequestInterface $request
     * @internal Set by extbase bootstrap internally.
     * @todo: Enable this interface method in v13.
     */
    // public function setRequest(ServerRequestInterface $request): void;

    /**
     * Returns TRUE if a certain feature, identified by $featureName
     * should be activated, FALSE for backwards-compatible behavior.
     *
     * This is an INTERNAL API used throughout Extbase and Fluid for providing backwards-compatibility.
     * Do not use it in your custom code!
     *
     * @internal
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13. Remove together with other extbase feature toggle related code.
     */
    public function isFeatureEnabled(string $featureName): bool;
}
