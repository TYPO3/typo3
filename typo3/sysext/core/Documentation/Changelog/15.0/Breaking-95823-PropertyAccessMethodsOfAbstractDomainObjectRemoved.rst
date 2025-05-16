.. include:: /Includes.rst.txt

.. _breaking-95823-1790589493:

==========================================================================
Breaking: #95823 - Property access methods of AbstractDomainObject removed
==========================================================================

See :issue:`95823`

Description
===========

The following internal methods have been removed from
:php:`\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject` and
:php:`\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface`:

*   :php:`_setProperty()`
*   :php:`_getProperty()`
*   :php:`_hasProperty()`

Extbase no longer reads and writes entity properties from inside the entity.
The persistence layer accesses the entity from outside via reflection, using
the methods :php:`getRawEntityPropertyValue()`,
:php:`setRawEntityPropertyValue()` and :php:`hasEntityProperty()` of
:php:`\TYPO3\CMS\Extbase\Reflection\ReflectionService`.

These methods work on the raw property values: property hooks, getters and
setters of the entity are not called. Private properties declared in the model
class itself are read, written and persisted as well, which was not the case
with the removed methods. Private properties of a parent class are not entity
properties. Virtual properties (property hooks without a backing value) hold no
state and are ignored.

..  important::

    The new methods of :php:`ReflectionService` are marked as
    :php:`@internal`. They exist for the persistence layer and are no
    replacement API for the removed methods.

Impact
======

Calling one of the removed methods results in a fatal PHP error.

Property hooks of Extbase models are no longer executed while the persistence
layer reconstitutes or persists an entity. Custom implementations of the
removed methods in a model are no longer called by Extbase.

Affected installations
======================

Installations with extensions that call :php:`_setProperty()`,
:php:`_getProperty()` or :php:`_hasProperty()` on Extbase domain objects, for
example to set the :php:`uid` of an entity in tests, or that override these
methods in a model.

Installations with Extbase models that rely on property hooks being executed
when records are loaded or persisted.

The extension scanner reports calls of the removed methods as weak matches, as
it only matches the method names and cannot tell which class a method is called
on. Models that override one of the removed methods are not reported.

Migration
=========

Read and write the property with PHP reflection. This works with TYPO3 v14
and v15:

..  code-block:: php

    // Before: $entity->_setProperty('uid', 42);
    (new \ReflectionProperty($entity, 'uid'))->setValue($entity, 42);

    // Before: $entity->_getProperty('title');
    $property = new \ReflectionProperty($entity, 'title');
    $title = $property->isInitialized($entity)
        ? $property->getValue($entity)
        : null;

    // Before: $entity->_hasProperty('title');
    $hasTitle = property_exists($entity, 'title');

Logic that has to run after an entity has been reconstituted belongs into
:php:`initializeObject()` of the model or an event listener for
:php:`\TYPO3\CMS\Extbase\Event\Persistence\AfterObjectThawedEvent`.

.. index:: PHP-API, PartiallyScanned, ext:extbase
