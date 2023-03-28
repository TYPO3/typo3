.. include:: /Includes.rst.txt

.. _feature-98132-1677928250:

===============================================================
Feature: #98132 - Extbase entity properties support union types
===============================================================

See :issue:`98132`

Description
===========

Extbase reflection now supports the detection of union types in entity properties.

Previously, whenever a union type was needed, union type declarations led to Extbase
not detecting any type at all, resulting in the property not being mapped. Union
types could be resolved via doc blocks however:

..  code-block:: php

    class Entity extends AbstractEntity
    {
        /**
         * @var ChildEntity|LazyLoadingProxy
         */
        private $property;
    }

Now this is possible:

..  code-block:: php

    class Entity extends AbstractEntity
    {
        private ChildEntity|LazyLoadingProxy $property;
    }

This is especially useful for lazy loaded relations where the property type is `LazyLoadingProxy|ChildEntity`.

There is something important to understand about how Extbase detects unions when
it comes to property mapping, i.e. when a database row is mapped onto an object.
In this case, Extbase needs to know the desired target type - no union, no
intersection, just one type. In order to achieve this, Extbase uses the first
declared type as a so-called primary type.

..  code-block:: php

    class Entity extends AbstractEntity
    {
        private string|int $property;
    }

In this case, `string` is the primary type. `int|string` would result in `int` as primary type.

There is one important thing to note and one exception to this rule. First of
all, `null` is not considered a type. `null|string` results in primary type
`string`, which is nullable. `null|string|int` also results in primary type
`string`. In fact, `null` means that all other types are nullable.
`null|string|int` boils down to `?string` or `?int`.

Secondly, `LazyLoadingProxy` is never detected as primary type because it is
just a proxy and not the actual target type, once loaded.

..  code-block:: php

    class Entity extends AbstractEntity
    {
        private LazyLoadingProxy|ChildEntity $property;
    }

Extbase supports this and detects `ChildEntity` as primary type, although
`LazyLoadingProxy` is the first item in the list. However, it is recommended to
place the actual type first, for consistency reasons: `ChildEntity|LazyLoadingProxy`.

A final word on `LazyObjectStorage`: `LazyObjectStorage` is a subclass of
`ObjectStorage`, therefore the following code works and has always worked:

..  code-block:: php

    class Entity extends AbstractEntity
    {
        /**
         * @var ObjectStorage<ChildEntity>
         * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
         */
        private ObjectStorage $property;
    }


Impact
======

As described above, the main impact is Extbase being able to detect and support
union type declarations for entity properties.

.. index:: PHP-API, ext:extbase
