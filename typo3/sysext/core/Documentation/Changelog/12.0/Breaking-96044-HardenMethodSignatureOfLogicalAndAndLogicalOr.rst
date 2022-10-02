.. include:: /Includes.rst.txt

.. _breaking-96044:

==========================================================================
Breaking: #96044 - Harden method signature of logicalAnd() and logicalOr()
==========================================================================

See :issue:`96044`

Description
===========

The method signature of :php:`\TYPO3\CMS\Extbase\Persistence\QueryInterface::logicalAnd()`
and :php:`\TYPO3\CMS\Extbase\Persistence\QueryInterface::logicalOr()` has changed.
As a consequence the method signature of :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Query::logicalAnd()`
and :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Query::logicalOr()` has changed as well.

Both methods do no longer accept an array as first parameter.

Both methods do indeed accept an infinite number of further constraints.

The :php:`logicalAnd()` method does now reliably return an instance of
:php:`\TYPO3\CMS\Extbase\Persistence\Generic\Qom\AndInterface` instance
while the :php:`logicalOr()` method returns a :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Qom\OrInterface`
instance.

Impact
======

This change impacts all usages of said methods with just one array parameter containing all constraints.

Affected Installations
======================

All installations that passed all constraints as array.

Migration
=========

The migration is the same for :php:`logicalAnd()` and :php:`logicalOr()`
since their method signature is the same. The upcoming example will show a
migration for a :php:`logicalAnd()` call.

**Example**:

..  code-block:: php

    $query = $this->createQuery();
    $query->matching($query->logicalAnd([
        $query->equals('propertyName1', 'value1'),
        $query->equals('propertyName2', 'value2'),
        $query->equals('propertyName3', 'value3'),
    ]));

In this case an array is used as one and only method argument. The migration is
easy and quickly done. Simply don't use an array:

..  code-block:: php

    $query = $this->createQuery();
    $query->matching($query->logicalAnd(
        $query->equals('propertyName1', 'value1'),
        $query->equals('propertyName2', 'value2'),
        $query->equals('propertyName3', 'value3'),
    ));

.. index:: PHP-API, FullyScanned, ext:extbase
