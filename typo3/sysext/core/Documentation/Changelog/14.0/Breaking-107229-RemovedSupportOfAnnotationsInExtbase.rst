..  include:: /Includes.rst.txt

..  _breaking-107229-1754602036:

==============================================================
Breaking: #107229 - Removed support for annotations in Extbase
==============================================================

See :issue:`107229`

Description
===========

Extbase no longer supports PHP annotations for models, data transfer objects
(DTOs), and controller actions. Use PHP attributes (for example
:php:`#[Extbase\Validate]`) instead. Attributes provide the same functionality
with better performance and native language support.

..  note::

    With this change, the third-party library :composer:`doctrine/annotations`
    is no longer required by Extbase.
    The library itself recommends switching to PHP attributes since
    `version 1.14.0 <https://github.com/doctrine/annotations/commit/cbc5a5188d9eecf2e878a201351d414a1bb3bee3>`__
    and has been
    `marked as abandoned <https://github.com/doctrine/annotations/commit/a64d7f063e1602bbbc8e70a07b6b5f555b8c98a7>`__.

..  important::

    PHPDoc annotations such as :php:`@var`, :php:`@return`, and similar are
    unaffected by this change, as they are handled by standard PHPDoc parsing,
    not by Doctrine.
    Explicit type annotations like :php:`@var ObjectStorage<FileReference>`
    remain fully supported.

Extbase previously relied on annotation parsing, typically detected when the
annotation namespace was imported, for example:

..  code-block:: php

    use TYPO3\CMS\Extbase\Annotation as Extbase;
    use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

    class MyModel extends AbstractEntity
    {
        /**
         * @Extbase\Validate("NotEmpty")
         */
        protected string $foo = '';
    }

Since TYPO3 now requires PHP 8.2 as the minimum version, the use of native PHP
attributes is preferred. All Extbase-related annotations have been available
as PHP attributes since TYPO3 v12.

Impact
======

By dropping support for Extbase annotations, only PHP attributes are now
supported. This provides more type safety and better integration with PHP’s
language features.
Developers benefit from a cleaner, faster, and more reliable implementation
without the need for the deprecated third-party annotation parser.

Affected installations
======================

All Extbase models, *Data Transfer Objects (DTOs)*, and controllers that use
Extbase annotations are affected.
This includes built-in annotations such as :php:`Cascade`, :php:`Lazy`, and
:php:`Validate`, as well as any custom annotation implementations.

..  _breaking-107229-1754602036-migration:

Migration
=========

Switch from Extbase annotations to native PHP attributes.

Before (with annotations)
-------------------------

..  code-block:: php

    use TYPO3\CMS\Extbase\Annotation as Extbase;
    use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

    class MyModel extends AbstractEntity
    {
        /**
         * @Extbase\Validate("NotEmpty")
         */
        protected string $foo = '';
    }

After (with PHP attributes)
---------------------------

..  code-block:: php

    use TYPO3\CMS\Extbase\Attribute as Extbase;
    use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

    class MyModel extends AbstractEntity
    {
        #[Extbase\Validate(['validator' => 'NotEmpty'])]
        protected string $foo = '';
    }

..  note::

    Attribute class namespaces have moved from
    :php:`\TYPO3\CMS\Extbase\Annotation` to :php:`\TYPO3\CMS\Extbase\Attribute`.

    A class alias map remains available for backward compatibility, but since
    the old namespaces are deprecated, developers should migrate to the new
    :php:`Attribute` namespace to prevent issues in the future.

..  index:: PHP-API, FullyScanned, ext:extbase
