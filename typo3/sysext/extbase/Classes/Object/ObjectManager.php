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

namespace TYPO3\CMS\Extbase\Object;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\Container\Container as ExtbaseContainer;

/**
 * Implementation of the default Extbase Object Manager
 *
 * @deprecated since v11, will be removed in v12. Use symfony DI and GeneralUtility::makeInstance() instead.
 *              See TYPO3 explained documentation for more information.
 */
class ObjectManager implements ObjectManagerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ExtbaseContainer
     */
    protected $objectContainer;

    /**
     * Constructs a new Object Manager
     *
     * @param ContainerInterface $container
     * @param ExtbaseContainer $objectContainer
     */
    public function __construct(ContainerInterface $container, ExtbaseContainer $objectContainer)
    {
        $this->container = $container;
        $this->objectContainer = $objectContainer;
    }

    /**
     * Serialization (sleep) helper.
     *
     * Removes properties of this object from serialization.
     * This action is necessary, since there might be closures used
     * in the accordant content objects (e.g. in FLUIDTEMPLATE) which
     * cannot be serialized. It's fine to reset $this->contentObjects
     * since elements will be recreated and are just a local cache,
     * but not required for runtime logic and behaviour.
     *
     * @see https://forge.typo3.org/issues/36820
     * @return array Names of the properties to be serialized
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function __sleep(): array
    {
        return [];
    }

    /**
     * Unserialization (wakeup) helper.
     *
     * Initializes the properties again that have been removed by
     * a call to the __sleep() method on serialization before.
     *
     * @see https://forge.typo3.org/issues/36820
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function __wakeup()
    {
        $this->__construct(
            GeneralUtility::getContainer(),
            GeneralUtility::getContainer()->get(ExtbaseContainer::class)
        );
    }

    /**
     * Returns a fresh or existing instance of the class specified by $className.
     *
     * @template T of object
     *
     * @param class-string<T> $className the name of the class to return an instance of
     * @param array ...$constructorArguments
     *
     * @return T the class instance
     *
     * @deprecated since TYPO3 10.4, will be removed in version 12.0
     */
    public function get(string $className, ...$constructorArguments): object
    {
        trigger_error('Class ' . __CLASS__ . ' is deprecated and will be removed in TYPO3 12.0', E_USER_DEPRECATED);
        if ($className === \DateTime::class) {
            return GeneralUtility::makeInstance($className, ...$constructorArguments);
        }

        if ($this->container->has($className)) {
            if ($constructorArguments === []) {
                $instance = $this->container->get($className);
                if (!is_object($instance)) {
                    throw new Exception('Invalid object name "' . $className . '". The PSR-11 container entry resolves to a non object.', 1562357346);
                }
                return $instance;
            }
            trigger_error($className . ' is available in the PSR-11 container. That means you should not try to instanciate it using constructor arguments. Falling back to legacy extbase based injection.', E_USER_DEPRECATED);
        }

        return $this->objectContainer->getInstance($className, $constructorArguments);
    }

    /**
     * Creates an instance of $className without calling its constructor.
     *
     * @template T of object
     *
     * @param class-string<T> $className the name of the class to return an instance of
     *
     * @return T the class instance
     *
     * @deprecated since v11, will be removed in v12. Does NOT log, has a v11 deprecation.rst file.
     *      Used in DataMapper, will be removed as breaking change in v12. Also drop doctrine/instantiator.
     */
    public function getEmptyObject(string $className): object
    {
        return $this->objectContainer->getEmptyObject($className);
    }
}
