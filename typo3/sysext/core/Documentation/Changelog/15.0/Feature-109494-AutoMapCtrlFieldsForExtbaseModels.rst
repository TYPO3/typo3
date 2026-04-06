.. include:: /Includes.rst.txt

.. _feature-109494-1744281656:

===============================================================
Feature: #109494 - Auto-map TCA "ctrl" fields to Extbase models
===============================================================

See :issue:`109494`

Description
===========

TCA capabilities defined in the `ctrl` section are now automatically
mapped to Extbase domain model properties, even if they are not defined
in the TCA `columns` section. This applies to the system capabilities for
which the Core does not create a default `columns` definition:

- `tstamp` (capability `UpdatedAt`)
- `crdate` (capability `CreatedAt`)
- `deleted` (capability `SoftDelete`)
- `sorting` (capability `SortByField`)

Other `ctrl` fields such as `hidden`, `starttime`, `endtime`, `fe_group`,
`editlock` and the description column already receive an automatically
created `columns` definition since TYPO3 v13.3 and were therefore already
readable by Extbase before this change.

:php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory`
now creates a passthrough column map for every system capability that has
no explicit `columns` definition. An explicit `columns` definition still
takes precedence and keeps its previous behaviour. The
:php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper` then
hydrates these fields like any other column.

These fields belong to the Core and are managed by it, so Extbase only
reads them: they are mapped read-only and are never written back to the
database, not even when the corresponding model property is changed.

Example
=======

Before, `tstamp` and `crdate` had to be duplicated in the `columns`
section to be readable by Extbase:

.. code-block:: php

   // TCA
   'ctrl' => [
       'tstamp' => 'tstamp',
       'crdate' => 'crdate',
   ],
   'columns' => [
       'tstamp' => ['config' => ['type' => 'passthrough']],
       'crdate' => ['config' => ['type' => 'passthrough']],
   ],

   // Extbase model
   class MyModel extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
   {
       protected ?int $tstamp = null;
       protected ?int $crdate = null;
   }

After the change, only the `ctrl` definition is needed and the redundant
`columns` definitions can be removed:

.. code-block:: php

   // TCA - no need to define tstamp/crdate in columns
   'ctrl' => [
       'tstamp' => 'tstamp',
       'crdate' => 'crdate',
   ],

   // Extbase model - unchanged, both properties are now hydrated
   class MyModel extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
   {
       protected ?int $tstamp = null;
       protected ?int $crdate = null;
   }

Impact
======

Extension developers can now remove redundant TCA `columns` definitions
for `tstamp`, `crdate`, `deleted` and `sorting`, reducing maintenance
effort.

The automatically mapped fields are read-only for Extbase. They are
available on the model after a record has been loaded, but changes to
them are not persisted. In particular, `deleted` cannot be set through a
model property: use
:php-short:`\TYPO3\CMS\Extbase\Persistence\RepositoryInterface::remove()`
or
:php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::remove()`
to soft-delete a record. `crdate` is set once when a record is created,
while `tstamp` is still updated automatically by the persistence layer on
every change. The in-memory model is not synchronized with these values:
reload the record after persisting if the current values are needed.

Because the fields are now mapped, a model property with one of these
names is hydrated from the database even if it was not declared in `columns`
before. Properties named `tstamp`, `crdate`, `deleted` or `sorting` that
Extbase previously ignored now receive the value of the corresponding system
field. Rename or remove such a property if it is not meant to represent that
field.

A model property with one of these names must have a single, resolvable
property type: an untyped, union or intersection typed property now
causes an
:php-short:`\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\Exception\UnknownPropertyTypeException`
while a row is mapped.

Models that declare these fields in the TCA `columns` section are not
affected: an explicit `columns` definition takes precedence, so such
fields keep their previous read and write behaviour.

.. index:: ext:extbase, TCA, Database
