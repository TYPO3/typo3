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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lowlevel\Controller\ConfigurationController;

class GlobalVariableProvider extends AbstractProvider
{
    /**
     * Blind configurations which should not be visible to mortal admins
     *
     * @var array
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
                    ],
                ],
            ],
            'HTTP' => [
                'cert' => '******',
                'ssl_key' => '******',
            ],
            'MAIL' => [
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
     *
     * @var string
     */
    protected string $globalVariableKey;

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
        $blindedConfigurationOptions = $this->blindedConfigurationOptions;

        // Hook for Processing blindedConfigurationOptions
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][ConfigurationController::class]['modifyBlindedConfigurationOptions'] ?? [] as $classReference) {
            $processingObject = GeneralUtility::makeInstance($classReference);
            $blindedConfigurationOptions = $processingObject->modifyBlindedConfigurationOptions($blindedConfigurationOptions, $this);
        }

        if (isset($blindedConfigurationOptions[$this->globalVariableKey])) {
            // Prepare blinding for all database connection types
            foreach (array_keys($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']) as $connectionName) {
                if ($connectionName !== 'Default') {
                    $blindedConfigurationOptions['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName] =
                        $blindedConfigurationOptions['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
                }
            }
            ArrayUtility::mergeRecursiveWithOverrule(
                $configurationArray,
                ArrayUtility::intersectRecursive($blindedConfigurationOptions[$this->globalVariableKey], $configurationArray)
            );
        }
        ArrayUtility::naturalKeySortRecursive($configurationArray);
        return $configurationArray;
    }
}
