..  include:: /Includes.rst.txt

..  _breaking-107229-1754602036:

==============================================================
Breaking: #107229 - Removed support for annotations in Extbase
==============================================================

See :issue:`107229`

Description
===========

Extbase no longer supports PHP annotations for Models, DTOs, and controller
actions. Use PHP attributes (e.g. :php:`#[Extbase\Validate]`) instead, which
provide the same functionality with better performance and native language
support. This also avoids using a third-party library (in this case
:composer:`doctrine/annotations`).

..  important::

    Note that usage of PHPDoc annotations like :php:`@var`, :php:`@return`
    and others are unaffected by this change, as they do not require specific
    Doctrine annotation parsing. Especially explicit parameter type annotations
    like :php:`@var ObjectStorage<FileReference>` are still fully supported.

Extbase made heavy use of annotation parsing, which can be detected via code
such as this, where the annotation namespace was imported:

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

Since PHP 8.2 is used as minimum requirement in TYPO3, the
well-established PHP attributes should be used instead. All Extbase-related
annotations are already usable as PHP attributes since TYPO3 v12.


Impact
======

By dropping support for Extbase annotations, the more type-safe and native use
of PHP attributes comes into play. This will enhance validation and ORM-related
integrations a lot, since developers can rely on well-established architectures
without the need of maintaining an additional third-party feature.


Affected installations
======================

All Extbase models, DTOs and Extbase controllers which make use of Extbase
annotations are affected. This covers all shipped annotations like
:php:`Cascade`, :php:`Lazy` and :php:`Validate`, as well as custom developed
annotations.


Migration
=========

Switch from Extbase annotations to native PHP attributes.

Before (with annotations):
--------------------------

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

After (with PHP Attributes):
----------------------------

..  code-block:: php

     use TYPO3\CMS\Extbase\Attribute as Extbase;
     use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

     class MyModel extends AbstractEntity
     {
         #[Extbase\Validate(['validator' => 'NotEmpty'])]
         protected string $foo = '';
     }

..  note::

    Namespaces of all attribute classes have been moved from
    :php:`TYPO3\CMS\Extbase\Annotation` to :php:`TYPO3\CMS\Extbase\Attribute`.

    A class alias map is provided to allow further usage of the previous
    namespaces. Since the previous namespaces are considered deprecated,
    developers should migrate usages of the attribute classes to avoid
    misbehaviour in the future.

..  index:: PHP-API, FullyScanned, ext:extbase
