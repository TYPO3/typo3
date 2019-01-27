<?php
declare(strict_types = 1);

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
namespace TYPO3\CMS\Extbase\Persistence;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;

/**
 * Class TYPO3\CMS\Extbase\Persistence\ClassesConfiguration
 */
final class ClassesConfigurationFactory implements SingletonInterface
{
    /**
     * @var FrontendInterface
     */
    private $cacheFrontend;

    /**
     * @param CacheManager|null $cacheManager can be null to disable caching in this factory
     */
    public function __construct(CacheManager $cacheManager = null)
    {
        $cacheIdentifier = 'extbase';

        $cacheFrontend = new NullFrontend($cacheIdentifier);
        if ($cacheManager !== null) {
            try {
                $cacheFrontend = $cacheManager->getCache($cacheIdentifier);
            } catch (\TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException $e) {
                // Handling this exception is not needed as $cacheFrontend is
                // a NullFrontend at this moment.
            }
        }

        $this->cacheFrontend = $cacheFrontend;
    }

    /**
     * @return ClassesConfiguration
     */
    public function createClassesConfiguration(): ClassesConfiguration
    {
        $cacheEntryIdentifier = 'PersistenceClasses_' . sha1(TYPO3_version . Environment::getProjectPath());

        if ($this->cacheFrontend->has($cacheEntryIdentifier)) {
            return new ClassesConfiguration($this->cacheFrontend->get($cacheEntryIdentifier));
        }

        $classes = [];
        foreach (GeneralUtility::makeInstance(PackageManager::class)->getActivePackages() as $activePackage) {
            $persistenceClassesFile = $activePackage->getPackagePath() . 'Configuration/Extbase/Persistence/Classes.php';
            if (file_exists($persistenceClassesFile)) {
                $definedClasses = require $persistenceClassesFile;
                if (is_array($definedClasses)) {
                    ArrayUtility::mergeRecursiveWithOverrule(
                        $classes,
                        $definedClasses,
                        true,
                        false
                    );
                }
            }
        }

        $classes = $this->inheritPropertiesFromParentClasses($classes);

        $this->cacheFrontend->set($cacheEntryIdentifier, $classes);

        return new ClassesConfiguration($classes);
    }

    /**
     * todo: this method is flawed, see https://forge.typo3.org/issues/87566
     *
     * @param array $classes
     * @return array
     */
    private function inheritPropertiesFromParentClasses(array $classes): array
    {
        foreach (array_keys($classes) as $className) {
            if (!isset($classes[$className]['properties'])) {
                $classes[$className]['properties'] = [];
            }

            /*
             * At first we need to clean the list of parent classes.
             * This methods is expected to be called for models that either inherit
             * AbstractEntity or AbstractValueObject, therefore we want to know all
             * parents of $className until one of these parents.
             */
            $relevantParentClasses = [];
            $parentClasses = class_parents($className);
            while (null !== $parentClass = array_shift($parentClasses)) {
                if (in_array($parentClass, [AbstractEntity::class, AbstractValueObject::class], true)) {
                    break;
                }

                $relevantParentClasses[] = $parentClass;
            }

            /*
             * Once we found all relevant parent classes of $class, we can check their
             * property configuration and merge theirs with the current one. This is necessary
             * to get the property configuration of parent classes in the current one to not
             * miss data in the model later on.
             */
            foreach ($relevantParentClasses as $currentClassName) {
                if (null === $properties = $classes[$currentClassName]['properties'] ?? null) {
                    continue;
                }

                ArrayUtility::mergeRecursiveWithOverrule($classes[$className]['properties'], $properties, true, false);
            }
        }

        return $classes;
    }
}
