.. include:: /Includes.rst.txt

.. _feature-103493-1711562309:

===========================================================
Feature: #103493 - Edit full record in "Check Links" module
===========================================================

See :issue:`103493`

Description
===========

Previously, the listing in the :guilabel:`Check Links` backend module
provided the possibility to edit the field of a record, a broken link has
been identified for. However, in some cases relevant context might be missing,
e.g. when editing redirect records.

Therefore, a new button has been introduced, which allows to edit the
full record of the broken link. The new button is placed next to the
existing - single field - edit button.

Impact
======

A new button is now displayed in the :guilabel:`Check Links` backend module,
allowing to edit the full record of a broken link.

.. index:: Backend, ext:linkvalidator
