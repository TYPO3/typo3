.. include:: /Includes.rst.txt

==========================================================
Breaking: #77765 - ExtJS notifications and dialogs removed
==========================================================

See :issue:`77765`

Description
===========

ExtJS notifications with the ExtJS components `TYPO3.Window` and `TYPO3.Dialog` have been removed from the TYPO3 Core.


Impact
======

Calling any of the JavaScript components above will result in a JavaScript error.


Affected Installations
======================

Any TYPO3 installations using custom extensions based on ExtJS.


Migration
=========

Use the JavaScript-based Modal and Dialog functionality that are part of the TYPO3 Core since v7.

For migration details `see the docs`_


.. _see the docs: https://docs.typo3.org/typo3cms/extensions/core/Changelog/7.2/Feature-66047-IntroduceJavascriptNotificationApi.html

.. index:: Backend, JavaScript
