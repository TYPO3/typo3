.. include:: /Includes.rst.txt

=====================================================================
Deprecation: #83475 - Aggregate validator information in class schema
=====================================================================

See :issue:`83475`

Description
===========

The method `\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::getActionMethodParameters` has been marked as deprecated
and will be removed in TYPO3 v10.0


Impact
======

The method was not considered public API and it is unlikely that the methods is used in the wild. If you rely on that
method, please migrate your code base.


Affected Installations
======================

All installations that use that method.


Migration
=========

Use the `ClassSchema` class and get all necessary information from it.
Example:

.. code-block:: php

	$reflectionService = $objectManager->get(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
	$methods = $reflectionService->getClassSchema($className)->getMethods();
	$actions = array_filter($methods, function($method){
	    return $method['isAction'];
	});

.. index:: PHP-API, FullyScanned
