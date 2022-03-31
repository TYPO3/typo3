.. include:: /Includes.rst.txt

=======================================================================
Feature: #88962 - Re-implement old PIDupinRootline TypoScript condition
=======================================================================

See :issue:`88962`

Description
===========

The :typoscript:`PIDupinRootline` condition in TypoScript has been reimplemented within the Symfony
expression language.

A new property :typoscript:`tree.rootLineParentIds` has been added to the :typoscript:`tree` object which
is available in the Symfony expression language to provide checks for all parent
page IDs of the current rootline.

Impact
======

When using the classic :typoscript:`PIDupinRootline` condition, you can easily switch to the
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
