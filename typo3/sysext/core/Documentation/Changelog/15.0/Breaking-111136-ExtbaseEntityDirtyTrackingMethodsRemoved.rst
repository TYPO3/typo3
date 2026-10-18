.. include:: /Includes.rst.txt

.. _breaking-111136-1792349978:

=================================================================
Breaking: #111136 - Extbase entity dirty tracking methods removed
=================================================================

See :issue:`111136`

Description
===========

Extbase kept the original ("clean") property values of a domain object inside
the object itself and asked the object whether it had been modified. This
dirty tracking has moved from the entity into the persistence layer, in line
with the concepts of the Doctrine ORM, where the original entity data and the
change set are managed outside of the entity.

The following methods have been removed from
:php:`\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject`:

*   :php:`_memorizeCleanState()`
*   :php:`_memorizePropertyCleanState()`
*   :php:`_getCleanProperties()`
*   :php:`_getCleanProperty()`
*   :php:`_isDirty()`
*   :php:`_isClone()`
*   :php:`_setClone()`
*   :php:`__clone()`
*   the protected method :php:`isPropertyDirty()`

The method :php:`_getCleanProperty()` has also been removed from
:php:`\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface`.

The interface :php:`\TYPO3\CMS\Extbase\Persistence\ObjectMonitoringInterface`
has been removed. :php:`\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface`
and :php:`\TYPO3\CMS\Extbase\Persistence\ObjectStorage` no longer implement it,
which removes :php:`_memorizeCleanState()` and :php:`_isDirty()` from
:php:`ObjectStorage` as well. The storage still tracks internally whether it
has been modified.

Domain objects are not flagged as clones anymore, a clone is an ordinary
object without any special state.

The replacement is the service
:php:`\TYPO3\CMS\Extbase\Persistence\Generic\CleanStateTracker`, which the
persistence layer uses itself. It keeps the original values of every object
that the persistence layer has loaded or inserted, and forgets them when the
object is garbage collected. The original values are not part of the entity
anymore, so they are neither copied by :php:`clone` nor contained in a
serialized entity. An object that has a uid, but that the persistence layer has
neither loaded nor inserted, is not managed: This is the case for an
unserialized or a cloned entity, and for an object that has been built by hand
and given a uid.

.. important::

    The :php:`CleanStateTracker` is marked as :php:`@internal`. Its public
    methods are offered as the replacement for the removed entity methods in
    TYPO3 v15 only. It has no stability promise beyond that: its shape may
    change in TYPO3 v16 without a further Breaking changelog entry.

Impact
======

Calling one of the removed methods on a domain object or an
:php:`ObjectStorage` results in a PHP error. A class that implements
:php:`ObjectMonitoringInterface` fails to load. Code that overrides the
protected method :php:`isPropertyDirty()` is not called anymore.

A domain object that overrides :php:`__clone()` and calls
:php:`parent::__clone()` fails with a PHP :php:`Error`, as the parent class
does not define the method anymore: :php:`Call to undefined method
\TYPO3\CMS\Extbase\DomainObject\AbstractEntity::__clone()`.

Objects that implement :php:`DomainObjectInterface` without extending
:php:`AbstractDomainObject` are not tracked anymore. The
:php:`CleanStateTracker` does not register original values for them, so they
have no change set, and an update writes none of their properties. Extend
:php:`\TYPO3\CMS\Extbase\DomainObject\AbstractEntity` or
:php:`\TYPO3\CMS\Extbase\DomainObject\AbstractValueObject` instead.

Passing an object that is not managed to
:php:`\TYPO3\CMS\Extbase\Persistence\Repository::update()`,
:php:`\TYPO3\CMS\Extbase\Persistence\Repository::add()` or the persistence
manager throws a
:php:`\TYPO3\CMS\Extbase\Persistence\Exception\UnmanagedObjectException`,
which extends :php:`UnknownObjectException`. The same exception is thrown when
the persistence layer reaches such an object in the relations of an object that
is persisted, for example a cloned child that is attached to a managed entity.
Nothing is written in this case. Before, such an object was written like a
managed one, which overwrote the record with all values of the object and added
the rows of its relations a second time.

Reading an unserialized or a cloned entity still works. It has no original
values, so the tracker reports it as not changed.

The form ViewHelpers no longer render the HTML comment
:html:`<!-- Object of type ... is without identity -->` for a clone of a new
object, as clones are not flagged anymore. A new object, cloned or not, renders
no identity field.

Affected installations
======================

Installations with extensions that

*   call one of the removed methods on domain objects or on an
    :php:`ObjectStorage`, for example to find out whether the user changed
    something in an edit form,
*   implement :php:`ObjectMonitoringInterface`, or
*   override :php:`isPropertyDirty()` in a domain object,
*   override :php:`__clone()` in a domain object and call
    :php:`parent::__clone()`,
*   implement :php:`DomainObjectInterface` without extending
    :php:`AbstractDomainObject`,
*   store entities serialized in a session or a cache, for example the state
    of a multi-step form, and update them later, or
*   update a clone of an entity.

The extension scanner reports calls of the removed methods as weak matches, as
it only matches the method names and cannot tell which class a method is called
on. The interface :php:`ObjectMonitoringInterface` is reported as a strong
match. A call of :php:`parent::__clone()` is not reported, as the scanner
cannot resolve :php:`parent::` calls.

Migration
=========

Entities that are stored and updated later
------------------------------------------

Do not store an entity that is updated later. A multi-step form keeps the
submitted values together with the identity of the entity, and lets the
property mapper convert them to the managed entity in the last step:

.. code-block:: php

   // Before: the entity is part of the form state and updated in the last step
   $formState['blog'] = $blog;
   // ...
   $this->blogRepository->update($formState['blog']);

   // After: the identity and the submitted values are part of the form state
   $formState['blog'] = ['__identity' => $blog->getUid(), 'title' => $title];
   // ...
   $configuration = new PropertyMappingConfiguration();
   $configuration->allowAllProperties();
   $configuration->setTypeConverterOption(
       PersistentObjectConverter::class,
       PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED,
       true
   );
   $blog = $this->propertyMapper->convert($formState['blog'], Blog::class, $configuration);
   $this->blogRepository->update($blog);

Cache or store the uid, or the data, instead of an entity that is updated
later, and fetch the entity from the repository when it is needed:

.. code-block:: php

   // Before
   $cache->set($cacheIdentifier, $blog);
   // ...
   $blog = $cache->get($cacheIdentifier);
   $blog->setTitle($title);
   $this->blogRepository->update($blog);

   // After
   $cache->set($cacheIdentifier, $blog->getUid());
   // ...
   $blog = $this->blogRepository->findByUid($cache->get($cacheIdentifier));
   $blog->setTitle($title);
   $this->blogRepository->update($blog);

Change the loaded object instead of a clone. To copy a record, build a new
instance and add it, instead of cloning and updating the entity:

.. code-block:: php

   // Before: the clone has the uid of the original and is updated
   $copy = clone $blog;
   $copy->setTitle($title);
   $this->blogRepository->update($copy);

   // After: change the loaded object
   $blog->setTitle($title);
   $this->blogRepository->update($blog);

   // After: copy the record
   $copy = new Blog();
   $copy->setTitle($title);
   $this->blogRepository->add($copy);

Entities that are serialized and unserialized after this change are not
managed. An entity that was serialized before this change contains the former
original values. They are ignored when it is unserialized, for example when a
form state of a running multi-step form is restored during an update.

Domain objects that override __clone()
--------------------------------------

Remove the call of :php:`parent::__clone()`. Resetting the uid in the own
:php:`__clone()` stays possible. The clone then is a new object that can be
passed to :php:`add()`:

.. code-block:: php

   // Before
   public function __clone()
   {
       parent::__clone();
       $this->uid = null;
   }

   // After
   public function __clone()
   {
       $this->uid = null;
   }

Dirty checks
------------

Inject :php:`\TYPO3\CMS\Extbase\Persistence\Generic\CleanStateTracker` and ask
it instead of the domain object:

*   :php:`$object->_isDirty()` becomes
    :php:`$cleanStateTracker->isEntityDirty($object)`
*   :php:`$object->_isDirty($propertyName)` becomes
    :php:`$cleanStateTracker->isEntityPropertyDirty($object, $propertyName)`
*   :php:`$objectStorage->_isDirty()` becomes
    :php:`$cleanStateTracker->isEntityDirty($objectStorage)`
*   :php:`$object->_getCleanProperties()` becomes
    :php:`$cleanStateTracker->getOriginalEntityData($object)`
*   :php:`$object->_getCleanProperty($propertyName)` becomes
    :php:`$cleanStateTracker->getOriginalEntityData($object)[$propertyName] ?? null`
*   :php:`$object->_memorizeCleanState()` and
    :php:`$objectStorage->_memorizeCleanState()` become
    :php:`$cleanStateTracker->setOriginalEntityData($object)`
*   :php:`$object->_memorizePropertyCleanState($propertyName)` becomes
    :php:`$cleanStateTracker->setOriginalEntityProperty($object, $propertyName)`
*   the changed properties with their original and current value are available
    with :php:`$cleanStateTracker->getEntityChangeSet($object)`
*   whether the persistence layer manages an object is answered by
    :php:`$cleanStateTracker->contains($object)`

Code that is not built by the container can get the service with
:php:`GeneralUtility::makeInstance(CleanStateTracker::class)`.

Ask whether anything has been changed, for a single property and for a
to-many relation:

.. code-block:: php

   // Before
   if ($object->_isDirty($propertyName)) {
       // ...
   }
   if ($object->getImages()->_isDirty()) {
       // ...
   }

   // After
   public function __construct(
       private readonly CleanStateTracker $cleanStateTracker,
   ) {}

   if ($this->cleanStateTracker->isEntityPropertyDirty($object, $propertyName)) {
       // ...
   }
   if ($this->cleanStateTracker->isEntityDirty($object->getImages())) {
       // ...
   }

Handle objects that are new or have been changed, for example for a
validation:

.. code-block:: php

   // Before
   if ($object->_isNew() || $object->_isDirty()) {
       // ...
   }

   // After
   if ($object->_isNew() || $this->cleanStateTracker->isEntityDirty($object)) {
       // ...
   }

A dirty check is not a way to find out whether an object has been persisted. A
new object has no original data and is never dirty, so the negated check does
not tell new objects from persisted ones. Ask the object itself:

.. code-block:: php

   // Before
   if (!$object->_isDirty()) {
       // not yet persisted
   }

   // After
   if ($object->_isNew()) {
       // not yet persisted
   }

The check for a new object that is not a clone is reduced to the check for a
new object:

.. code-block:: php

   // Before
   if ($object->_isNew() && !$object->_isClone()) {
       // ...
   }

   // After
   if ($object->_isNew()) {
       // ...
   }

.. index:: PHP-API, PartiallyScanned, ext:extbase
