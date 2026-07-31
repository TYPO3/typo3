.. include:: /Includes.rst.txt

.. _deprecation-110347-1785518009:

=============================================================
Deprecation: #110347 - LazyLoadingProxy and LazyObjectStorage
=============================================================

See :issue:`110347`

Description
===========

The internal Extbase lazy loading classes

*   :php:`TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy`
*   :php:`TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage`
*   :php:`TYPO3\CMS\Extbase\Persistence\Generic\LoadingStrategyInterface`

have been marked as deprecated. Lazy relations of domain model properties
annotated with :php:`#[Extbase\ORM\Lazy]` are created as
:ref:`native PHP lazy objects <feature-110347-1785518009>` by the DataMapper
instead: 1:n and m:n relations as lazy ghost instances of
:php:`TYPO3\CMS\Extbase\Persistence\ObjectStorage`, 1:1 and n:1 relations as
lazy proxy instances of the actual target entity class.

Impact
======

Instantiating :php:`LazyLoadingProxy` or :php:`LazyObjectStorage` will trigger
a PHP :php:`E_USER_DEPRECATED` error. All three classes will be removed in
TYPO3 v16.0.

TYPO3 Core does not create instances of these classes anymore. Existing
:php:`instanceof` checks against them evaluate to :php:`false` for relations
created by the DataMapper.

Affected installations
======================

Installations with extensions that reference these classes, for example in
union type declarations of lazy model properties, in :php:`instanceof` checks,
or by calling :php:`LazyLoadingProxy->_loadRealInstance()`.

The extension scanner reports usages as weak match.

Migration
=========

No change is required for declaring lazy relations, the
:php:`#[Extbase\ORM\Lazy]` attribute remains the single way to do so.

Union type declarations can be reduced to the actual entity type:

..  code-block:: php

    // Before
    #[Extbase\ORM\Lazy]
    protected Category|LazyLoadingProxy|null $parent = null;

    // After
    #[Extbase\ORM\Lazy]
    protected ?Category $parent = null;

Calls to :php:`_loadRealInstance()` can be removed altogether, since the
property value is an instance of the target entity class in all cases and
initializes itself transparently on first access:

..  code-block:: php

    // Before
    public function getParent(): ?Category
    {
        if ($this->parent instanceof LazyLoadingProxy) {
            $this->parent->_loadRealInstance();
        }
        return $this->parent;
    }

    // After
    public function getParent(): ?Category
    {
        return $this->parent;
    }

Code that needs to know whether a lazy object has been initialized (for
example to avoid triggering database queries) can use native PHP reflection:

..  code-block:: php

    $reflection = new \ReflectionClass(ObjectStorage::class);
    if ($reflection->isUninitializedLazyObject($storage)) {
        // storage content has not been fetched from the database yet
    }

.. index:: PHP-API, FullyScanned, ext:extbase
