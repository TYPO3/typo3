.. include:: /Includes.rst.txt

============================================================
Feature: #86964 - Allow getting class property default value
============================================================

See :issue:`86964`

Description
===========

It is now possible to get the default value of a class property when using the :php:`TYPO3\CMS\Extbase\Reflection\ReflectionService`.

.. code-block:: php

    class MyClass
    {
        public $myProperty = 'foo';
    }

    $property = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class)
        ->getClassSchema(MyClass::class)
        ->getProperty('myProperty');

    $defaultValue = $property->getDefaultValue(); // "foo"

.. index:: PHP-API, ext:extbase
