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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;

/**
 * a  singleton class
 */
class t3lib_object_tests_singleton implements SingletonInterface
{
}

/**
 * test class A that depends on B and C
 */
class t3lib_object_tests_a
{
    public t3lib_object_tests_b $b;
    public t3lib_object_tests_c $c;

    /**
     * @param \t3lib_object_tests_c $c
     * @param \t3lib_object_tests_b $b
     */
    public function __construct(\t3lib_object_tests_c $c, \t3lib_object_tests_b $b)
    {
        $this->b = $b;
        $this->c = $c;
    }
}

/**
 * test class A that depends on B and C and has a third default parameter in constructor
 */
class t3lib_object_tests_amixed_array
{
    public t3lib_object_tests_b $b;
    public t3lib_object_tests_c $c;
    public array $myvalue;

    /**
     * @param \t3lib_object_tests_b $b
     * @param \t3lib_object_tests_c $c
     * @param array $myvalue
     */
    public function __construct(\t3lib_object_tests_b $b, \t3lib_object_tests_c $c, array $myvalue = ['some' => 'default'])
    {
        $this->b = $b;
        $this->c = $c;
        $this->myvalue = $myvalue;
    }
}

/**
 * test class A that depends on B and C and has a third default parameter in constructor that defaults to NULL
 */
class t3lib_object_tests_amixed_null
{
    public t3lib_object_tests_b $b;
    public t3lib_object_tests_c $c;

    /**
     * @var mixed|null
     */
    public $myvalue;

    /**
     * @param \t3lib_object_tests_b $b
     * @param \t3lib_object_tests_c $c
     * @param mixed $myvalue
     */
    public function __construct(\t3lib_object_tests_b $b, \t3lib_object_tests_c $c, $myvalue = null)
    {
        $this->b = $b;
        $this->c = $c;
        $this->myvalue = $myvalue;
    }
}

/**
 * test class A that depends on B and C and has a third default parameter in constructor
 */
class t3lib_object_tests_amixed_array_singleton implements SingletonInterface
{
    public t3lib_object_tests_b $b;
    public t3lib_object_tests_c $c;
    public array $myvalue;

    /**
     * @param \t3lib_object_tests_b $b
     * @param \t3lib_object_tests_c $c
     * @param array $someDefaultParameter
     */
    public function __construct(\t3lib_object_tests_b $b, \t3lib_object_tests_c $c, array $someDefaultParameter = ['some' => 'default'])
    {
        $this->b = $b;
        $this->c = $c;
        $this->myvalue = $someDefaultParameter;
    }
}

/**
 * test class B that depends on C
 */
class t3lib_object_tests_b implements SingletonInterface
{
    public t3lib_object_tests_c $c;

    /**
     * @param \t3lib_object_tests_c $c
     */
    public function __construct(\t3lib_object_tests_c $c)
    {
        $this->c = $c;
    }
}

/**
 * test class C without dependencies
 */
class t3lib_object_tests_c implements SingletonInterface
{
}

/**
 * test class B-Child that extends Class B (therefore depends also on Class C)
 */
class t3lib_object_tests_b_child extends \t3lib_object_tests_b
{
}

interface t3lib_object_tests_someinterface extends SingletonInterface
{
}

/**
 * Test class D implementing Serializable
 */
class t3lib_object_tests_serializable implements \Serializable
{
    public function serialize()
    {
    }
    public function unserialize($s)
    {
    }
}

/**
 * class which implements an Interface
 */
class t3lib_object_tests_someimplementation implements \t3lib_object_tests_someinterface
{
}

/**
 * test class B-Child that extends Class B (therefore depends also on Class C)
 */
class t3lib_object_tests_b_child_someimplementation extends \t3lib_object_tests_b implements \t3lib_object_tests_someinterface
{
}

/**
 * class which depends on an Interface
 */
class t3lib_object_tests_needsinterface
{
    /**
     * @param \t3lib_object_tests_someinterface $i
     */
    public function __construct(\t3lib_object_tests_someinterface $i)
    {
        $this->dependency = $i;
    }
}

/**
 * Prototype classes that depend on each other
 */
class t3lib_object_tests_cyclic1
{
    /**
     * @param \t3lib_object_tests_cyclic2 $c
     */
    public function __construct(\t3lib_object_tests_cyclic2 $c)
    {
    }
}

class t3lib_object_tests_cyclic2
{
    /**
     * @param \t3lib_object_tests_cyclic1 $c
     */
    public function __construct(\t3lib_object_tests_cyclic1 $c)
    {
    }
}

class t3lib_object_tests_cyclic1WithSetterDependency
{
    /**
     * @param \t3lib_object_tests_cyclic2WithSetterDependency $c
     */
    public function injectFoo(\t3lib_object_tests_cyclic2WithSetterDependency $c): void
    {
    }
}

class t3lib_object_tests_cyclic2WithSetterDependency
{
    /**
     * @param \t3lib_object_tests_cyclic1WithSetterDependency $c
     */
    public function injectFoo(\t3lib_object_tests_cyclic1WithSetterDependency $c): void
    {
    }
}

/**
 * class which has setter injections defined
 */
class t3lib_object_tests_injectmethods
{
    public t3lib_object_tests_b $b;
    public t3lib_object_tests_b_child $bchild;

    /**
     * @param \t3lib_object_tests_b $o
     */
    public function injectClassB(\t3lib_object_tests_b $o): void
    {
        $this->b = $o;
    }

    /**
     * @TYPO3\CMS\Extbase\Annotation\Inject
     * @param \t3lib_object_tests_b_child $o
     */
    public function setClassBChild(\t3lib_object_tests_b_child $o): void
    {
        $this->bchild = $o;
    }
}

/**
 * class which needs extension settings injected
 */
class t3lib_object_tests_injectsettings
{
    public array $settings;

    /**
     * @param array $settings
     */
    public function injectExtensionSettings(array $settings): void
    {
        $this->settings = $settings;
    }
}

class t3lib_object_tests_resolveablecyclic1 implements SingletonInterface
{
    public t3lib_object_tests_resolveablecyclic2 $o2;

    /**
     * @param \t3lib_object_tests_resolveablecyclic2 $cyclic2
     */
    public function __construct(\t3lib_object_tests_resolveablecyclic2 $cyclic2)
    {
        $this->o2 = $cyclic2;
    }
}

class t3lib_object_tests_resolveablecyclic2 implements SingletonInterface
{
    public t3lib_object_tests_resolveablecyclic1 $o1;
    public t3lib_object_tests_resolveablecyclic3 $o3;

    /**
     * @param \t3lib_object_tests_resolveablecyclic1 $cyclic1
     */
    public function injectCyclic1(\t3lib_object_tests_resolveablecyclic1 $cyclic1): void
    {
        $this->o1 = $cyclic1;
    }

    /**
     * @param \t3lib_object_tests_resolveablecyclic3 $cyclic3
     */
    public function injectCyclic3(\t3lib_object_tests_resolveablecyclic3 $cyclic3): void
    {
        $this->o3 = $cyclic3;
    }
}

class t3lib_object_tests_resolveablecyclic3 implements SingletonInterface
{
    public t3lib_object_tests_resolveablecyclic1 $o1;

    /**
     * @param \t3lib_object_tests_resolveablecyclic1 $cyclic1
     */
    public function injectCyclic1(\t3lib_object_tests_resolveablecyclic1 $cyclic1): void
    {
        $this->o1 = $cyclic1;
    }
}

class t3lib_object_tests_class_with_injectsettings
{
    /**
     * @param \t3lib_object_tests_resolveablecyclic1 $c1
     */
    public function injectFoo(\t3lib_object_tests_resolveablecyclic1 $c1): void
    {
    }

    /**
     * @param \t3lib_object_tests_resolveablecyclic1 $c1
     */
    public function injectingFoo(\t3lib_object_tests_resolveablecyclic1 $c1): void
    {
    }

    /**
     * @param array $settings
     */
    public function injectSettings(array $settings): void
    {
    }
}

/*
 *  a Singleton requires a Prototype for Injection -> allowed, autowiring active, but in development context we write a log message, as it is bad practice and most likely points to some logic error.
If a Singleton requires a Singleton for Injection -> allowed, autowiring active
If a Prototype requires a Prototype for Injection -> allowed, autowiring active
If a Prototype requires a Singleton for Injection -> allowed, autowiring active
 */

class t3lib_object_singleton implements SingletonInterface
{
}

class t3lib_object_prototype
{
}

class t3lib_object_singletonNeedsPrototype implements SingletonInterface
{
    public t3lib_object_prototype $dependency;

    /**
     * @param \t3lib_object_prototype $dependency
     */
    public function injectDependency(\t3lib_object_prototype $dependency): void
    {
        $this->dependency = $dependency;
    }
}

class t3lib_object_singletonNeedsSingleton implements SingletonInterface
{
    public t3lib_object_singleton $dependency;

    /**
     * @param \t3lib_object_singleton $dependency
     */
    public function injectDependency(\t3lib_object_singleton $dependency): void
    {
        $this->dependency = $dependency;
    }
}

class t3lib_object_prototypeNeedsPrototype
{
    public t3lib_object_prototype $dependency;

    /**
     * @param \t3lib_object_prototype $dependency
     */
    public function injectDependency(\t3lib_object_prototype $dependency): void
    {
        $this->dependency = $dependency;
    }
}

class t3lib_object_prototypeNeedsSingleton
{
    public t3lib_object_singleton $dependency;

    /**
     * @param \t3lib_object_singleton $dependency
     */
    public function injectDependency(\t3lib_object_singleton $dependency): void
    {
        $this->dependency = $dependency;
    }
}

class t3lib_object_singletonNeedsPrototypeInConstructor implements SingletonInterface
{
    /**
     * @param \t3lib_object_prototype $dependency
     */
    public function __construct(\t3lib_object_prototype $dependency)
    {
        $this->dependency = $dependency;
    }
}

class t3lib_object_singletonNeedsSingletonInConstructor implements SingletonInterface
{
    /**
     * @param \t3lib_object_singleton $dependency
     */
    public function __construct(\t3lib_object_singleton $dependency)
    {
        $this->dependency = $dependency;
    }
}

class t3lib_object_prototypeNeedsPrototypeInConstructor
{
    /**
     * @param \t3lib_object_prototype $dependency
     */
    public function __construct(\t3lib_object_prototype $dependency)
    {
        $this->dependency = $dependency;
    }
}

class t3lib_object_prototypeNeedsSingletonInConstructor
{
    /**
     * @param \t3lib_object_singleton $dependency
     */
    public function __construct(\t3lib_object_singleton $dependency)
    {
        $this->dependency = $dependency;
    }
}

/**
 * Class that needs initialization after instantiation
 */
class t3lib_object_tests_initializable extends AbstractDomainObject
{
    protected bool $initialized = false;

    public function initializeObject(): void
    {
        if ($this->initialized) {
            throw new \Exception('initializeObject was called a second time', 1433944932);
        }
        $this->initialized = true;
    }

    /**
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }
}
