
.. include:: ../../Includes.txt

=============================================================================
Feature: #71876 - Make new content element wizard tab sort order configurable
=============================================================================

See :issue:`71876`

Description
===========

It is possible to influence the order of the tabs in the new content element
wizard by setting `before` and `after` values in Page TSconfig:

.. code-block:: typoscript

    mod.wizards.newContentElement.wizardItems.special.before = common
    mod.wizards.newContentElement.wizardItems.forms.after = common,special

.. index:: Backend, TSConfig
