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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent;

class GlobalVariableProvider extends AbstractProvider
{
    use BlindedConnectionOptionsTrait;

    /**
     * Blind configurations which should not be visible to mortal admins
     */
    protected array $blindedConfigurationOptions = [
        'TYPO3_CONF_VARS' => [
            'BE' => [
                'installToolPassword' => '******',
            ],
            'DB' => [
                'database' => '******',
                'host' => '******',
                'password' => '******',
                'port' => '******',
                'socket' => '******',
                'username' => '******',
                'Connections' => [
                    'Default' => [
                        'dbname' => '******',
                        'host' => '******',
                        'password' => '******',
                        'port' => '******',
                        'user' => '******',
                        'unix_socket' => '******',
                        'url' => '******',
                    ],
                ],
            ],
            'HTTP' => [
                'cert' => '******',
                'ssl_key' => '******',
            ],
            'MAIL' => [
                'dsn' => '******',
                'transport_smtp_encrypt' => '******',
                'transport_smtp_password' => '******',
                'transport_smtp_server' => '******',
                'transport_smtp_username' => '******',
            ],
            'SYS' => [
                'encryptionKey' => '******',
            ],
        ],
    ];

    /**
     * The $GLOBALS key to be processed
     */
    protected string $globalVariableKey;

    public function __construct(protected readonly EventDispatcherInterface $eventDispatcher) {}

    public function __invoke(array $attributes): self
    {
        parent::__invoke($attributes);

        if (!($attributes['globalVariableKey'] ?? false)) {
            throw new \RuntimeException('Attribute \'globalVariableKey\' must be set to use ' . __CLASS__, 1606478088);
        }

        $this->globalVariableKey = $attributes['globalVariableKey'];
        return $this;
    }

    public function getConfiguration(): array
    {
        $configurationArray = $GLOBALS[$this->globalVariableKey] ?? [];
        $blindedConfigurationOptions = $this->eventDispatcher->dispatch(
            new ModifyBlindedConfigurationOptionsEvent($this->blindedConfigurationOptions, $this->identifier)
        )->getBlindedConfigurationOptions();

        if (isset($blindedConfigurationOptions[$this->globalVariableKey])) {
            // Prepare blinding for all database connections
            if (is_array($blindedConfigurationOptions[$this->globalVariableKey]['DB']['Connections'] ?? null)) {
                $blindedConfigurationOptions[$this->globalVariableKey]['DB']['Connections'] = $this->applyBlindedConnectionOptionsToAllConnections(
                    $blindedConfigurationOptions[$this->globalVariableKey]['DB']['Connections'],
                    $configurationArray['DB']['Connections'] ?? []
                );
            }
            if (is_array($blindedConfigurationOptions[$this->globalVariableKey]['MAIL'] ?? null)) {
                $blindedConfigurationOptions[$this->globalVariableKey]['MAIL'] = $this->applyBlindedMailOptionsToAllProfiles(
                    $blindedConfigurationOptions[$this->globalVariableKey]['MAIL'],
                    $configurationArray['MAIL']['profiles'] ?? []
                );
            }
            ArrayUtility::mergeRecursiveWithOverrule(
                $configurationArray,
                ArrayUtility::intersectRecursive($blindedConfigurationOptions[$this->globalVariableKey], $configurationArray)
            );
        }
        ArrayUtility::naturalKeySortRecursive($configurationArray);
        return $configurationArray;
    }

    /**
     * Mail profiles use the same keys as the global MAIL configuration, so its blinded
     * options apply to every configured profile as well.
     *
     * @param array<string, mixed> $blindedMailOptions
     * @param mixed $profiles configured mail profiles, keyed by profile name
     * @return array<string, mixed>
     */
    private function applyBlindedMailOptionsToAllProfiles(array $blindedMailOptions, mixed $profiles): array
    {
        $defaultOptions = $blindedMailOptions;
        unset($defaultOptions['profiles']);
        if (!is_array($profiles) || $defaultOptions === []) {
            return $blindedMailOptions;
        }
        $blindedProfiles = is_array($blindedMailOptions['profiles'] ?? null) ? $blindedMailOptions['profiles'] : [];
        foreach (array_keys($profiles) as $profileName) {
            $profileOptions = $blindedProfiles[$profileName] ?? [];
            // Options already declared for a specific profile take precedence
            $blindedProfiles[$profileName] = is_array($profileOptions)
                ? array_replace_recursive($defaultOptions, $profileOptions)
                : $defaultOptions;
        }
        $blindedMailOptions['profiles'] = $blindedProfiles;
        return $blindedMailOptions;
    }
}
