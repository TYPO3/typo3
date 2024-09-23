.. include:: /Includes.rst.txt

.. _feature-103493-1711562309:

===========================================================
Feature: #103493 - Edit full record in "Check Links" module
===========================================================

See :issue:`103493`

Description
===========

Previously, the listing in the :guilabel:`Check Links` backend module
provided the possibility to edit the field of a record that has been identified
as having a broken link. However, in some cases relevant context might be missing,
e.g. when editing redirect records.

Therefore, a new button has been introduced which allows the full record of the
broken link to be edited. The new button is placed next to the
existing - single field - edit button.

Impact
======

A new button is now displayed in the :guilabel:`Check Links` backend module,
allowing the full record of a broken link to be edited.

.. index:: Backend, ext:linkvalidator
