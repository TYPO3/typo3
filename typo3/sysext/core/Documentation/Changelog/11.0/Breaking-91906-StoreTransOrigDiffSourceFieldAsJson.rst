.. include:: /Includes.rst.txt

================================================================
Breaking: #91906 - Store TransOrigDiffSourceField as json string
================================================================

See :issue:`91906`

Description
===========

The TCA field :php:`['tableName']['ctrl']['transOrigDiffSourceField']` - often set to
:php:`l18n_diffsource` - stores the state of the source language record a translated
record has been created from. This is used if content in the source language
of a record has been changed to hint editors for a potentially needed update
of the translated record.

The storage format of this field has been changed from a PHP serialized string
to a json encoded string.

Impact
======

Usages of this field can be expected to be core internal. The impact on existing
instances in low since it's unlikely that an extension uses the field content.


Affected Installations
======================

Installations with multi language sites are affected and should run the
upgrade wizard.


Migration
=========

Run "Admin Tools" -> "Upgrade" -> "Upgrade Wizard" -> "Migrate transOrigDiffSourceField field to json encoded string."
to adapt existing rows to the new storage format.

.. index:: Database, NotScanned, ext:core
