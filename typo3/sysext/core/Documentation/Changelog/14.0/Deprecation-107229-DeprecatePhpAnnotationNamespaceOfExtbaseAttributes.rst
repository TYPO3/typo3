..  include:: /Includes.rst.txt

..  _deprecation-107229-1760116732:

==================================================================================
Deprecation: #107229 - Deprecate :php:`Annotation` namespace of Extbase attributes
==================================================================================

See :issue:`107229`

Description
===========

With the :ref:`removed support for PHP annotations in Extbase <breaking-107229-1754602036>`,
the class namespace :php:`TYPO3\CMS\Extbase\Annotation` no longer reflects
classes with PHP annotations; it contains PHP attributes only. The class
namespace of all PHP attributes with support for Models, DTOs, and controller
actions in Extbase context is moved to :php:`TYPO3\CMS\Extbase\Attribute`.
Developers and integrators are advised to migrate existing class usages to the
new namespace. The deprecated namespace will be removed in TYPO3 v15.


Impact
======

Usage of the deprecated class namespace is no longer recommended, but will
continue working until TYPO3 v15.


Affected installations
======================

Instances using the deprecated class namespaces for Extbase attributes in
Models, DTOs, and controller actions are affected.


Migration
=========

Albeit from :ref:`migrating PHP annotations to PHP attributes <breaking-107229-1754602036-migration>`,
existing usages of the class namespace :php:`TYPO3\CMS\Extbase\Annotation`
should be migrated to :php:`TYPO3\CMS\Extbase\Attribute`:

..  code-block:: diff

    -TYPO3\CMS\Extbase\Annotation
    +TYPO3\CMS\Extbase\Attribute

Before:
-------

..  code-block:: php
    :emphasize-lines: 1

    use TYPO3\CMS\Extbase\Annotation as Extbase;
    use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

    class MyModel extends AbstractEntity
    {
        #[Extbase\Validate(['validator' => 'NotEmpty'])]
        protected string $foo = '';
    }

After:
------

..  code-block:: php
    :emphasize-lines: 1

    use TYPO3\CMS\Extbase\Attribute as Extbase;
    use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

    class MyModel extends AbstractEntity
    {
        #[Extbase\Validate(['validator' => 'NotEmpty'])]
        protected string $foo = '';
    }

..  index:: PHP-API, FullyScanned, ext:extbase
