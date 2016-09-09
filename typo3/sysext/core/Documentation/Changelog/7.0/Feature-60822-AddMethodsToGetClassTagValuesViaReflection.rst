
.. include:: ../../Includes.txt

=================================================================
Feature: #60822 - Class annotations in extbase reflection service
=================================================================

See :issue:`60822`

Description
===========

The extbase reflection service can now return tags/annotations added to a class.

Suppose the given class:

.. code-block:: php

   /**
    * @SomeClassAnnotation A value
    */
    class Foo {
    }


Those annotation can be fetched with the reflection service:

.. code-block:: php

    $service = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();
    $classValues = $service->getClassTagsValues('Foo');
    $classValue = $service->getClassTagValue('Foo', 'SomeClassAnnotation');


Impact
======

Getting class tags by ReflectionService is now possible.
