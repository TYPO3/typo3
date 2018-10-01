<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

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
 * Utility for dealing with extension model related helper functions
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class ExtensionModelUtility
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Map a legacy extension array to an object
     *
     * @param array $extensionArray
     * @return \TYPO3\CMS\Extensionmanager\Domain\Model\Extension
     */
    public function mapExtensionArrayToModel(array $extensionArray)
    {
        /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension */
        $extension = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class);
        $extension->setExtensionKey($extensionArray['key']);
        if (isset($extensionArray['version'])) {
            $extension->setVersion($extensionArray['version']);
        }
        if (isset($extensionArray['constraints'])) {
            $extension->setDependencies($this->convertDependenciesToObjects(serialize($extensionArray['constraints'])));
        }
        return $extension;
    }

    /**
     * Converts string dependencies to an object storage of dependencies
     *
     * @param string $dependencies
     * @return \SplObjectStorage
     */
    public function convertDependenciesToObjects($dependencies)
    {
        $dependenciesObject = new \SplObjectStorage();
        $unserializedDependencies = unserialize($dependencies, ['allowed_classes' => false]);
        if (!is_array($unserializedDependencies)) {
            return $dependenciesObject;
        }
        foreach ($unserializedDependencies as $dependencyType => $dependencyValues) {
            // Dependencies might be given as empty string, e.g. conflicts => ''
            if (!is_array($dependencyValues)) {
                continue;
            }
            foreach ($dependencyValues as $dependency => $versions) {
                if ($dependencyType && $dependency) {
                    $versionNumbers = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionsStringToVersionNumbers($versions);
                    $lowest = $versionNumbers[0];
                    if (count($versionNumbers) === 2) {
                        $highest = $versionNumbers[1];
                    } else {
                        $highest = '';
                    }
                    /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyObject */
                    $dependencyObject = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class);
                    $dependencyObject->setType($dependencyType);
                    // dynamically migrate 'cms' dependency to 'core' dependency
                    // see also \TYPO3\CMS\Core\Package\Package::getPackageMetaData
                    $dependencyObject->setIdentifier($dependency === 'cms' ? 'core' : $dependency);
                    $dependencyObject->setLowestVersion($lowest);
                    $dependencyObject->setHighestVersion($highest);
                    $dependenciesObject->attach($dependencyObject);
                    unset($dependencyObject);
                }
            }
        }
        return $dependenciesObject;
    }
}
