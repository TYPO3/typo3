.. include:: /Includes.rst.txt

========================================================
Feature: #93794 - Override TCA description with TSconfig
========================================================

See :issue:`93794`

Description
===========

The TCA description, introduced in :issue:`85410`, allows to define a description
for a TCA field, next to its label. Since the purpose of a field may change
depending on the current page, it is now possible to override the TCA
description property with page TSconfig.

.. code-block:: typoscript

   TCEFORM.aTable.aField.description = override description

As already known from other properties, this can also be configured for a
specific language.

.. code-block:: typoscript

   TCEFORM.aTable.aField.description.de = override description for DE

The option can be used on a per record type basis, too.

.. code-block:: typoscript

   TCEFORM.aTable.aField.types.aType.description = override description for aType

Also referencing language labels is supported.

.. code-block:: typoscript

   TCEFORM.aTable.aField.description = LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:override_description

.. note::

   The new option can not only be used to override an existing property,
   but also to set a description for a field, that has not yet been
   configured a description in TCA.

.. index:: Backend, TCA, TSConfig, ext:backend
