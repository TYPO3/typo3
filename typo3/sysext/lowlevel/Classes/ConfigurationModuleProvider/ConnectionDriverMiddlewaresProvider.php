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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent;

class ConnectionDriverMiddlewaresProvider extends AbstractProvider
{
    /**
     * Blind configurations which should not be visible to mortal admins
     */
    protected array $blindedConfigurationOptions = [
        'doctrine-dbal-driver-middlewares' => [
            'Raw' => [
                'Connections' => [
                    'Default' => [
                        'database' => '******',
                        'host' => '******',
                        'password' => '******',
                        'port' => '******',
                        'socket' => '******',
                        'username' => '******',
                    ],
                ],
            ],
        ],
    ];

    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly ConnectionPool $connectionPool,
    ) {}

    public function getConfiguration(): array
    {
        $configurationArray = $this->connectionPool->getConnectionMiddlewareConfigurationArrayForLowLevelConfiguration();
        $blindedConfigurationOptions = $this->eventDispatcher->dispatch(
            new ModifyBlindedConfigurationOptionsEvent($this->blindedConfigurationOptions, 'doctrine-dbal-driver-middlewares')
        )->getBlindedConfigurationOptions();
        foreach (array_keys($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']) as $connectionName) {
            foreach ($configurationArray['Connections'][$connectionName] as &$middleware) {
                $middlewareTarget = $middleware['target'] ?? '';
                $middleware['targetImplementedInterfaces'] = ($middlewareTarget !== '' && class_exists($middlewareTarget))
                    ? (class_implements($middlewareTarget) ?: [])
                    : [];
            }
            if ($connectionName !== 'Default') {
                $blindedConfigurationOptions['doctrine-dbal-driver-middlewares']['Raw']['Connections'][$connectionName] =
                    $blindedConfigurationOptions['doctrine-dbal-driver-middlewares']['Raw']['Connections']['Default'];
            }
        }
        ArrayUtility::mergeRecursiveWithOverrule(
            $configurationArray,
            ArrayUtility::intersectRecursive($blindedConfigurationOptions['doctrine-dbal-driver-middlewares'], $configurationArray)
        );
        ArrayUtility::naturalKeySortRecursive($configurationArray);
        return $configurationArray;
    }
}
