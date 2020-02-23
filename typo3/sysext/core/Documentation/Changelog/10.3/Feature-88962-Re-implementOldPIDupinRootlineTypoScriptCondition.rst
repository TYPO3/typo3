.. include:: ../../Includes.txt

=======================================================================
Feature: #88962 - Re-implement old PIDupinRootline TypoScript condition
=======================================================================

See :issue:`88962`

Description
===========

The :ts:`PIDupinRootline` condition in TypoScript has been reimplemented within the Symfony
expression language.

A new property :ts:`tree.rootLineParentIds` has been added to the :ts:`tree` object which
is available in the Symfony expression language to provide checks for all parent
page IDs of the current rootline.

Impact
======

When using the classic :ts:`PIDupinRootline` condition, you can easily switch to the
condition with the new expression:

Old TypoScript condition syntax:

.. code-block:: typoscript

   [PIDupinRootline = 30]
       page.10.value = I'm on any subpage of page with uid=30.
   [END]

New TypoScript condition syntax:

.. code-block:: typoscript

   [30 in tree.rootLineParentIds]
       page.10.value = I'm on any subpage of page with uid=30.
   [end]

.. index:: Backend, Frontend, TypoScript
