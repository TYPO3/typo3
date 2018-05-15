<?php
namespace TYPO3\CMS\Extbase\Object\Container;

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
 * TYPO3 Dependency Injection container
 */
class ClassInfoFactory
{
    /**
     * Factory metod that builds a ClassInfo Object for the given classname - using reflection
     *
     * @param string $className The class name to build the class info for
     * @throws Exception\UnknownObjectException
     * @return \TYPO3\CMS\Extbase\Object\Container\ClassInfo the class info
     */
    public function buildClassInfoFromClassName($className)
    {
        if ($className === 'DateTime') {
            return new \TYPO3\CMS\Extbase\Object\Container\ClassInfo($className, [], [], false, false, []);
        }
        try {
            $reflectedClass = new \ReflectionClass($className);
        } catch (\Exception $e) {
            throw new \TYPO3\CMS\Extbase\Object\Container\Exception\UnknownObjectException('Could not analyse class: "' . $className . '" maybe not loaded or no autoloader? ' . $e->getMessage(), 1289386765, $e);
        }
        $constructorArguments = $this->getConstructorArguments($reflectedClass);
        $injectMethods = $this->getInjectMethods($reflectedClass);
        $injectProperties = $this->getInjectProperties($reflectedClass);
        $isSingleton = $this->getIsSingleton($className);
        $isInitializeable = $this->getIsInitializeable($className);
        return new \TYPO3\CMS\Extbase\Object\Container\ClassInfo($className, $constructorArguments, $injectMethods, $isSingleton, $isInitializeable, $injectProperties);
    }

    /**
     * Build a list of constructor arguments
     *
     * @param \ReflectionClass $reflectedClass
     * @return array of parameter infos for constructor
     */
    private function getConstructorArguments(\ReflectionClass $reflectedClass)
    {
        $reflectionMethod = $reflectedClass->getConstructor();
        if (!is_object($reflectionMethod)) {
            return [];
        }
        $result = [];
        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            /* @var $reflectionParameter \ReflectionParameter */
            $info = [];
            $info['name'] = $reflectionParameter->getName();
            if ($reflectionParameter->getClass()) {
                $info['dependency'] = $reflectionParameter->getClass()->getName();
            }

            if ($reflectionParameter->isDefaultValueAvailable()) {
                $info['defaultValue'] = $reflectionParameter->getDefaultValue();
            }

            $result[] = $info;
        }
        return $result;
    }

    /**
     * Build a list of inject methods for the given class.
     *
     * @param \ReflectionClass $reflectedClass
     * @throws \Exception
     * @return array (nameOfInjectMethod => nameOfClassToBeInjected)
     */
    private function getInjectMethods(\ReflectionClass $reflectedClass)
    {
        $result = [];
        $reflectionMethods = $reflectedClass->getMethods();
        if (is_array($reflectionMethods)) {
            foreach ($reflectionMethods as $reflectionMethod) {
                if ($reflectionMethod->isPublic() && $this->isNameOfInjectMethod($reflectionMethod->getName())) {
                    $reflectionParameter = $reflectionMethod->getParameters();
                    if (isset($reflectionParameter[0])) {
                        if (!$reflectionParameter[0]->getClass()) {
                            throw new \Exception(
                                'Method "' . $reflectionMethod->getName() . '" of class "' . $reflectedClass->getName() . '" is marked as setter for Dependency Injection, but does not have a type annotation',
                                1476108030
                            );
                        }
                        $result[$reflectionMethod->getName()] = $reflectionParameter[0]->getClass()->getName();
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Build a list of properties to be injected for the given class.
     *
     * @param \ReflectionClass $reflectedClass
     * @return array (nameOfInjectProperty => nameOfClassToBeInjected)
     */
    private function getInjectProperties(\ReflectionClass $reflectedClass)
    {
        $result = [];
        $reflectionProperties = $reflectedClass->getProperties();
        if (is_array($reflectionProperties)) {
            foreach ($reflectionProperties as $reflectionProperty) {
                $reflectedProperty = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Reflection\PropertyReflection::class, $reflectedClass->getName(), $reflectionProperty->getName());
                if ($reflectedProperty->isTaggedWith('inject') && $reflectedProperty->getName() !== 'settings') {
                    $varValues = $reflectedProperty->getTagValues('var');
                    if (count($varValues) === 1) {
                        $result[$reflectedProperty->getName()] = ltrim($varValues[0], '\\');
                    }
                }
            }
        }
        return $result;
    }

    /**
     * This method checks if given method can be used for injection
     *
     * @param string $methodName
     * @return bool
     */
    private function isNameOfInjectMethod($methodName)
    {
        if (
            substr($methodName, 0, 6) === 'inject'
            && $methodName[6] === strtoupper($methodName[6])
            && $methodName !== 'injectSettings'
        ) {
            return true;
        }
        return false;
    }

    /**
     * This method is used to determine if a class is a singleton or not.
     *
     * @param string $classname
     * @return bool
     */
    private function getIsSingleton($classname)
    {
        return in_array(\TYPO3\CMS\Core\SingletonInterface::class, class_implements($classname));
    }

    /**
     * This method is used to determine of the object is initializeable with the
     * method initializeObject.
     *
     * @param string $classname
     * @return bool
     */
    private function getIsInitializeable($classname)
    {
        return method_exists($classname, 'initializeObject');
    }
}
