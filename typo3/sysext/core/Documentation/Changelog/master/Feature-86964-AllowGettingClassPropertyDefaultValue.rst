.. include:: ../../Includes.txt

============================================================
Feature: #86964 - Allow getting class property default value
============================================================

See :issue:`86964`

Description
===========

It is now possible to get the default value of a class property when using the ``ReflectionService``.

.. code-block:: php

	class MyClass
	{
	    public $myProperty = 'foo';
	}

	$property = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class)
	    ->getClassSchema(MyClass::class)
	    ->getProperty('myProperty');

	$defaultValue = $property['defaultValue']; // "foo"

.. index:: PHP-API, ext:extbase, NotScanned
