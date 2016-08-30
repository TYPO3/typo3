<?php
namespace TYPO3\CMS\Core\Resource\Driver;

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

/**
 * Registry for driver classes.
 */
class DriverRegistry implements \TYPO3\CMS\Core\SingletonInterface
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
     * Creates this object.
     */
    public function __construct()
    {
        $driverConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'];
        foreach ($driverConfigurations as $shortName => $driverConfig) {
            $shortName = $shortName ?: $driverConfig['shortName'];
            $this->registerDriverClass($driverConfig['class'], $shortName, $driverConfig['label'], $driverConfig['flexFormDS']);
        }
    }

    /**
     * Registers a driver class with an optional short name.
     *
     * @param string $className
     * @param string $shortName
     * @param string $label
     * @param string $flexFormDataStructurePathAndFilename
     * @return bool TRUE if registering succeeded
     * @throws \InvalidArgumentException
     */
    public function registerDriverClass($className, $shortName = null, $label = null, $flexFormDataStructurePathAndFilename = null)
    {
        // check if the class is available for TYPO3 before registering the driver
        if (!class_exists($className)) {
            throw new \InvalidArgumentException('Class ' . $className . ' does not exist.', 1314979197);
        }

        if (!in_array(\TYPO3\CMS\Core\Resource\Driver\DriverInterface::class, class_implements($className), true)) {
            throw new \InvalidArgumentException('Driver ' . $className . ' needs to implement the DriverInterface.', 1387619575);
        }
        if ($shortName === '') {
            $shortName = $className;
        }
        if (array_key_exists($shortName, $this->drivers)) {
            // Return immediately without changing configuration
            if ($this->drivers[$shortName] === $className) {
                return true;
            } else {
                throw new \InvalidArgumentException('Driver ' . $shortName . ' is already registered.', 1314979451);
            }
        }
        $this->drivers[$shortName] = $className;
        $this->driverConfigurations[$shortName] = [
            'class' => $className,
            'shortName' => $shortName,
            'label' => $label,
            'flexFormDS' => $flexFormDataStructurePathAndFilename
        ];
        return true;
    }

    /**
     * @return void
     */
    public function addDriversToTCA()
    {
        $driverFieldConfig = &$GLOBALS['TCA']['sys_file_storage']['columns']['driver']['config'];
        $configurationFieldConfig = &$GLOBALS['TCA']['sys_file_storage']['columns']['configuration']['config'];
        foreach ($this->driverConfigurations as $driver) {
            $label = $driver['label'] ?: $driver['class'];
            $driverFieldConfig['items'][$driver['shortName']] = [$label, $driver['shortName']];
            if ($driver['flexFormDS']) {
                $configurationFieldConfig['ds'][$driver['shortName']] = $driver['flexFormDS'];
            }
        }
    }

    /**
     * Returns a class name for a given class name or short name.
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
        if (!array_key_exists($shortName, $this->drivers)) {
            throw new \InvalidArgumentException(
                'Desired storage "' . $shortName . '" is not in the list of available storages.',
                1314085990);
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
        return array_key_exists($shortName, $this->drivers);
    }
}
