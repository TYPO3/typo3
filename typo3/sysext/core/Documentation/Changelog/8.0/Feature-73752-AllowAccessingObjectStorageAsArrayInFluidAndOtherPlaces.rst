
.. include:: /Includes.rst.txt

==================================================================================
Feature: #73752 - Allow accessing ObjectStorage as array in Fluid and other places
==================================================================================

See :issue:`73752`

Description
===========

Creates an alias of `toArray()` allowing the method to be called as `getArray()`
which in turn allows the method to be called transparently from
`ObjectAccess::getPropertyPath`, enabling access in Fluid and other places.


Impact
======

By creating an extremely simple aliasing of `toArray()` on ObjectStorage allowing
it to be called as `getArray()` enables:

.. code-block:: php

	ObjectAccess::getPropertyPath($subject, 'objectstorageproperty.array.4') to get the 4th element

.. code-block:: text

	{myObject.objectstorageproperty.array.4} in Fluid (including {myObject.objectstorageproperty.array.{dynamicIndex}} in v8)

.. index:: Fluid, PHP-API
