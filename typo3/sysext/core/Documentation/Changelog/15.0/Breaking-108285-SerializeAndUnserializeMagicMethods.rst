..  include:: /Includes.rst.txt

..  _breaking-108285-1792310400:

=======================================================================
Breaking: #108285 - Core uses __serialize() and __unserialize() methods
=======================================================================

See :issue:`108285`

Description
===========

PHP 8.5 deprecates the magic methods :php:`__sleep()` and :php:`__wakeup()`
in favor of :php:`__serialize()` and :php:`__unserialize()`. All TYPO3 Core
classes have been migrated to the new methods:

*   :php-short:`\TYPO3\CMS\Core\Log\Logger`
*   :php-short:`\TYPO3\CMS\Core\Resource\FileReference`
*   :php-short:`\TYPO3\CMS\Core\Security\BlockSerializationTrait`
*   :php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\Query`
*   :php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\QueryResult`
*   :php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings`
*   :php-short:`\TYPO3\CMS\Extbase\Reflection\ReflectionService`
*   :php-short:`\TYPO3\CMS\Form\Domain\Finishers\FinisherVariableProvider`

:php-short:`\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject`, the base
class of all Extbase entities and value objects, implements
:php:`__serialize()` and :php:`__unserialize()` as well.

The internal deserializer used by :php-short:`\TYPO3\CMS\Core\Registry` and
by upgrade wizards now also rejects classes with a user-defined
:php:`__unserialize()` method, unless that method is provided by
:php-short:`\TYPO3\CMS\Core\Security\BlockSerializationTrait` or the class is
listed in
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['deserialization']['allowedClassNames']`.

Impact
======

When a class or one of its parents implements :php:`__serialize()` or
:php:`__unserialize()`, PHP no longer calls :php:`__sleep()` or
:php:`__wakeup()`. Such methods in classes extending one of the classes above,
including any Extbase domain model, are silently ignored.

Serialized data written before the update, for example in caches, sessions or
the state of a form, may no longer be restored correctly.

Affected installations
======================

Installations with extensions that implement :php:`__sleep()` or
:php:`__wakeup()` in a class extending one of the classes mentioned above,
especially in Extbase domain models extending
:php-short:`\TYPO3\CMS\Extbase\DomainObject\AbstractEntity` or
:php-short:`\TYPO3\CMS\Extbase\DomainObject\AbstractValueObject`, or that
call :php:`parent::__sleep()` or :php:`parent::__wakeup()` of these classes.

Migration
=========

Replace :php:`__sleep()` with :php:`__serialize()`, which returns an array of
the data to be serialized, and :php:`__wakeup()` with :php:`__unserialize()`,
which receives that array and restores the object state. Call the parent
methods where the parent class implements them:

..  code-block:: php
    :caption: Before

    public function __sleep(): array
    {
        return ['title'];
    }

    public function __wakeup(): void
    {
        $this->initializeRuntimeState();
    }

..  code-block:: php
    :caption: After

    public function __serialize(): array
    {
        return ['title' => $this->title];
    }

    public function __unserialize(array $data): void
    {
        $this->title = $data['title'];
        $this->initializeRuntimeState();
    }

Flush all caches after the update. Pending form submissions and session data
containing serialized objects of the affected classes have to be re-created.

..  index:: PHP-API, NotScanned
