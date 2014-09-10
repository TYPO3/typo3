=================================================================
Feature: #60822 - Class annotations in extbase reflection service
=================================================================

Description
===========

The extbase reflection service can now return tags/annotations added to a class.

Suppose the given class:

::

/**
 * @SomeClassAnnotation A value
 */
 class Foo {
 }

..

Those annotation can be fetched with the reflection service:

::

$service = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();
$classValues = $service->getClassTagsValues('Foo');
$classValue = $service->getClassTagValue('Foo', 'SomeClassAnnotation');

..

Impact
======

Getting class tags by ReflectionService is now possible.
