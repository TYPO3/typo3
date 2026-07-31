.. include:: /Includes.rst.txt

.. _feature-110347-1785518009:

===============================================================
Feature: #110347 - Native lazy objects for Extbase lazy loading
===============================================================

See :issue:`110347`

Description
===========

Extbase now uses `native PHP lazy objects <https://www.php.net/manual/en/language.oop5.lazy-objects.php>`__
(available since PHP 8.4) to implement lazy loading of relations in domain
models annotated with the :php:`#[Extbase\ORM\Lazy]` attribute.

Previously, two dedicated proxy classes were used, which are now deprecated:

*   :php:`TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage` for 1:n and
    m:n relations
*   :php:`TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy` for 1:1 and
    n:1 relations

The DataMapper now creates the following constructs instead:

*   For 1:n and m:n relations, a **lazy ghost** instance of the regular
    :php:`TYPO3\CMS\Extbase\Persistence\ObjectStorage` class. The storage
    fetches its content from the persistence layer on first access.
*   For 1:1 and n:1 relations, a **lazy proxy** instance of the actual target
    entity class. The related record is fetched on first access, and the proxy
    then transparently forwards all calls to the mapped entity.

Concept
-------

A native lazy object is a real instance of its class: A lazy loaded
:php:`Category` parent *is* a :php:`Category`, and a lazy loaded
:php:`ObjectStorage` *is* an :php:`ObjectStorage`. The PHP engine tracks the
initialization state and invokes an initializer on first property access. There
is no foreign proxy class anymore that only mimics the API of the real object.

The uid of a lazy 1:1 or n:1 relation is available **without** database
access, since it is known from the parent's database row and set eagerly on
the uninitialized proxy. Dirty checking, URI generation and form rendering
therefore do not trigger relation loading anymore.

Benefits
--------

*   :php:`instanceof` checks against the target entity class are now true for
    uninitialized lazy relations.
*   Native property types can be used for lazy properties. The union type
    workaround is no longer needed:

    ..  code-block:: php

        // Before
        #[Extbase\ORM\Lazy]
        protected Category|LazyLoadingProxy|null $parent = null;

        public function getParent(): ?Category
        {
            if ($this->parent instanceof LazyLoadingProxy) {
                $this->parent->_loadRealInstance();
            }
            return $this->parent;
        }

        // After
        #[Extbase\ORM\Lazy]
        protected ?Category $parent = null;

        public function getParent(): ?Category
        {
            return $this->parent;
        }

*   Type declarations of methods consuming such relations can rely on the
    actual model class.
*   Lazy relations no longer carry references to the
    :php:`DataMapper` or the parent object as object state.

Serialization
-------------

Serializing Extbase entities and object storages is now well-defined:

*   Serializing a lazy relation initializes it first and serializes the
    **actual entity data**. Previously, the internal proxy state (raw field
    value, property name and a back reference to the whole parent object
    graph, in older TYPO3 versions even the DataMapper state) was serialized.
*   :php:`ObjectStorage` now implements :php:`__serialize()` and
    :php:`__unserialize()`. The contained objects are stored as a plain list
    and the internal :php:`spl_object_hash()` based bookkeeping is rebuilt on
    :php:`unserialize()`. Calling :php:`detach()`, :php:`contains()` or
    :php:`offsetGet()` on an unserialized storage now works correctly.
    Previously, the storage silently kept stale object hashes of the original
    process and those methods did not work on the restored storage.
*   Payloads serialized with older TYPO3 versions can still be unserialized.

Impact
======

Lazy loading works transparently for domain models using the
:php:`#[Extbase\ORM\Lazy]` attribute. **No migration is required**: Existing
models keep working, the attribute remains the single way to declare a lazy
relation.

Extension authors may optionally simplify their models:

*   Union type declarations like :php:`Category|LazyLoadingProxy|null` can be
    reduced to the actual entity type, for example :php:`?Category`.
*   Calls to :php:`LazyLoadingProxy->_loadRealInstance()` and
    :php:`instanceof LazyLoadingProxy` checks in getters can be removed, the
    property value is an instance of the target class in all cases.
*   Whether a lazy object has been initialized can be determined with native
    PHP reflection:

    ..  code-block:: php

        $isUninitialized = new \ReflectionClass(ObjectStorage::class)
            ->isUninitializedLazyObject($storage);

Behavioral notes:

*   Calling :php:`count()` on an uninitialized lazy object storage now fully
    initializes the storage with one query. Previously, a dedicated
    :sql:`COUNT` query was executed without initializing the storage.
*   If a lazy 1:1 or n:1 relation points to a record that cannot be resolved
    anymore (for example a deleted or hidden record), the parent property is
    reset to :php:`null` on first access, as before. Code holding the proxy
    instance itself encounters an empty entity instance instead of the
    previous :php:`null` return value of :php:`_loadRealInstance()`.

.. index:: PHP-API, ext:extbase
