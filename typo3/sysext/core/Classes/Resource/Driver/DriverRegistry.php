<?php

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

namespace TYPO3\CMS\Core\Resource\Driver;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Registry for driver classes.
 */
#[Autoconfigure(public: true)]
class DriverRegistry
{
    /**
     * @var array
     */
    protected $drivers = [];

    /**
     * @var array
     */
    protected $driverConfigurations = [];

    /**
     * Creates this object with the drivers registered with #[AsFileStorageDriver]
     * and in $TYPO3_CONF_VARS. A driver registered with the attribute takes
     * precedence over one registered in $TYPO3_CONF_VARS with the same identifier.
     *
     * @param array<string, class-string<DriverInterface>> $driverClasses driver classes registered with #[AsFileStorageDriver]
     * @param ContainerInterface|null $driverFactories driver and adapter factories registered with #[AsFileStorageDriver]
     */
    public function __construct(
        array $driverClasses = [],
        protected readonly ?ContainerInterface $driverFactories = null,
        protected readonly LoggerInterface $logger = new NullLogger(),
    ) {
        foreach ($driverClasses as $identifier => $className) {
            $this->registerDriverClass($className, (string)$identifier);
        }
        $driverConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] ?? [];
        foreach ($driverConfigurations as $shortName => $driverConfig) {
            $shortName = $shortName ?: $driverConfig['shortName'] ?? '';
            if (!empty($driverConfig['flexFormDS'])) {
                trigger_error(
                    'The file storage driver "' . $shortName . '" registers a "flexFormDS", which is not evaluated anymore since TYPO3 v15. Storages are configured in config/system/file-storages.yaml, document the driver options for its "configuration" map instead and remove "flexFormDS".',
                    E_USER_DEPRECATED
                );
            }
            if ($this->isRegisteredWithAttribute((string)$shortName, $driverClasses)) {
                if (($driverClasses[$shortName] ?? null) !== ($driverConfig['class'] ?? '')) {
                    $this->logger->warning('The file storage driver "{identifier}" registered in $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'fal\'][\'registeredDrivers\'] is ignored, as a driver with this identifier is registered with #[AsFileStorageDriver].', ['identifier' => $shortName]);
                }
                continue;
            }
            $this->registerDriverClass($driverConfig['class'] ?? '', $shortName, $driverConfig['label'] ?? '', $driverConfig['flexFormDS'] ?? '');
        }
    }

    /**
     * Creates the driver of a file storage from its configuration
     *
     * @param array<string, mixed> $configuration The "configuration" of the storage
     * @throws \InvalidArgumentException if the driver is not registered
     */
    public function createDriver(string $identifier, array $configuration): DriverInterface
    {
        if ($this->driverFactories?->has($identifier)) {
            $factory = $this->driverFactories->get($identifier);
            if ($factory instanceof FilesystemAdapterFactoryInterface) {
                return GeneralUtility::makeInstance(FlysystemDriver::class, $configuration, $factory);
            }
            return $factory->createDriver($configuration);
        }
        return GeneralUtility::makeInstance($this->getDriverClass($identifier), $configuration);
    }

    /**
     * @param array<string, class-string<DriverInterface>> $driverClasses
     */
    protected function isRegisteredWithAttribute(string $identifier, array $driverClasses): bool
    {
        return isset($driverClasses[$identifier]) || $this->driverFactories?->has($identifier);
    }

    /**
     * Registers a driver class with an optional short name.
     *
     * @param string $className
     * @param string|null $shortName
     * @param string $label
     * @param string $flexFormDataStructurePathAndFilename No longer evaluated, storages are configured in config/system/file-storages.yaml
     * @return bool TRUE if registering succeeded
     * @throws \InvalidArgumentException
     */
    public function registerDriverClass($className, $shortName = null, $label = null, $flexFormDataStructurePathAndFilename = null)
    {
        // todo: Default of $shortName must be empty string, not null.
        $shortName = (string)$shortName;

        // check if the class is available for TYPO3 before registering the driver
        if (!class_exists($className)) {
            throw new \InvalidArgumentException('Class ' . $className . ' does not exist.', 1314979197);
        }

        if (!in_array(DriverInterface::class, class_implements($className) ?: [], true)) {
            throw new \InvalidArgumentException('Driver ' . $className . ' needs to implement the DriverInterface.', 1387619575);
        }
        if ($shortName === '') {
            $shortName = $className;
        }
        if ($this->driverFactories?->has($shortName)) {
            throw new \InvalidArgumentException('Driver ' . $shortName . ' is already registered by a factory with #[AsFileStorageDriver].', 1794408871);
        }
        if (array_key_exists($shortName, $this->drivers)) {
            // Return immediately without changing configuration
            if ($this->drivers[$shortName] === $className) {
                return true;
            }
            throw new \InvalidArgumentException('Driver ' . $shortName . ' is already registered.', 1314979451);
        }
        $this->drivers[$shortName] = $className;
        $this->driverConfigurations[$shortName] = [
            'class' => $className,
            'shortName' => $shortName,
            'label' => $label,
            'flexFormDS' => $flexFormDataStructurePathAndFilename,
        ];
        return true;
    }

    /**
     * Returns a class name for a given class name or short name.
     * Drivers created by a FileStorageDriverFactoryInterface have no class known in advance.
     *
     * @param string $shortName
     * @return string The class name
     * @throws \InvalidArgumentException
     */
    public function getDriverClass($shortName)
    {
        if (in_array($shortName, $this->drivers) && class_exists($shortName)) {
            return $shortName;
        }
        if ($this->driverFactories?->has($shortName)) {
            if ($this->driverFactories->get($shortName) instanceof FilesystemAdapterFactoryInterface) {
                return FlysystemDriver::class;
            }
            throw new \InvalidArgumentException(
                'The driver of storage "' . $shortName . '" is created by a factory, its class is not known in advance.',
                1792161904
            );
        }
        if (!array_key_exists($shortName, $this->drivers)) {
            throw new \InvalidArgumentException(
                'Desired storage "' . $shortName . '" is not in the list of available storages.',
                1314085990
            );
        }
        return $this->drivers[$shortName];
    }

    /**
     * Checks if the given driver exists
     *
     * @param string $shortName Name of the driver
     * @return bool TRUE if the driver exists, FALSE otherwise
     */
    public function driverExists($shortName)
    {
        return array_key_exists($shortName, $this->drivers) || $this->driverFactories?->has($shortName) === true;
    }
}
