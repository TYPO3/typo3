.. include:: /Includes.rst.txt

.. _breaking-101822-1693575438:

==========================================================================================
Breaking: #101822 - Change callback interruption in `@typo3/backend/document-save-actions`
==========================================================================================

See :issue:`101822`

Description
===========

The JavaScript module :js:`@typo3/backend/document-save-actions` is used in
FormEngine and Scheduler context mainly to disable the submit button in the
according forms, where also a spinner is rendered within the button to visualize
a running action.

Over the time, the module took over some tasks that logically belong to
FormEngine, which lead to slimming down the module. In a further effort, jQuery
has been removed from said module, leading to a change in behavior how the
callback chain can be aborted.

Native JavaScript events cannot get asked whether event propagation has been
stopped, making changes in the callbacks necessary. All callbacks registered via
:js:`DocumentSaveActions.getInstance().addPreSubmitCallback()` now need to
return a boolean value.

Impact
======

Using :js:`stop[Immediate]Propagation()` on events passed into registered
callbacks is now unsupported and may lead to undefined behavior.


Affected installations
======================

All extensions using :js:`DocumentSaveActions.getInstance().addPreSubmitCallback()`
are affected.


Migration
=========

Callbacks now need to return a boolean value, where returning :js:`false` will
abort the callback execution chain.

.. index:: Backend, JavaScript, NotScanned, ext:backend
