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

/**
 * Blinded configuration options are declared for the "Default" database connection only,
 * although an instance may configure any number of additional connections carrying the
 * same kind of options. This shares the declaration with all configured connections.
 *
 * @internal only to be used within the lowlevel configuration module providers
 */
trait BlindedConnectionOptionsTrait
{
    /**
     * @param array<string, mixed> $blindedConnectionOptions blinded options, keyed by connection name
     * @param array<string, mixed> $connections configured connections, keyed by connection name
     * @return array<string, mixed>
     */
    protected function applyBlindedConnectionOptionsToAllConnections(
        array $blindedConnectionOptions,
        array $connections,
    ): array {
        $defaultOptions = $blindedConnectionOptions['Default'] ?? null;
        if (!is_array($defaultOptions) || $defaultOptions === []) {
            return $blindedConnectionOptions;
        }
        foreach (array_keys($connections) as $connectionName) {
            $connectionName = (string)$connectionName;
            $connectionOptions = $blindedConnectionOptions[$connectionName] ?? [];
            // Options already declared for a specific connection take precedence
            $blindedConnectionOptions[$connectionName] = is_array($connectionOptions)
                ? array_replace_recursive($defaultOptions, $connectionOptions)
                : $defaultOptions;
        }
        return $blindedConnectionOptions;
    }
}
