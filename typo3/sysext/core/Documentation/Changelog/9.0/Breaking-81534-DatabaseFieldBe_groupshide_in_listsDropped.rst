.. include:: /Includes.rst.txt

=================================================================
Breaking: #81534 - Database field be_groups:hide_in_lists dropped
=================================================================

See :issue:`81534`

Description
===========

The database field hide_in_lists of table be_groups has been dropped without substitution.

* The property has been dropped from PHP class :php:`TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup` along with
  the getter and setter methods :php:`->setHideInList` and :php:`->getHideInList`
* The TCA column :php:`hide_in_lists` has been dropped, the field is no longer configured and shown in the backend.
* The database field definition for :php:`hide_in_lists` has been dropped.


Impact
======

The special group configuration hide_in_lists has been removed.


Affected Installations
======================

An instance may break in the unlikely case that an extension relies on field existence or uses
the extbase model getter or setter.


Migration
=========

The field usage should be dropped. If that is not possible and a special functionality has been bound to that
field it should be mimicked by extending TCA, declaring the database field in an extension and maybe extending
the extbase model.

.. index:: Database, TCA, PartiallyScanned
