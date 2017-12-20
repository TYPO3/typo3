
.. include:: ../../Includes.txt

=====================================================
Deprecation: #62363 - TSFE->JSeventFuncCalls disabled
=====================================================

See :issue:`62363`

Description
===========

TYPO3 CMS provides a way to register direct JS calls to be added to the body tag of the frontend output
to allow several functions to register for e.g. "onload". Nowadays this is done via JS frameworks directly,
or via JS variables.

The functionality has been marked as deprecated.

Impact
======

The core does not use this functionality anymore. Installations with menus using "GMENU_LAYERS",
which has been removed from the core a while ago, but still use it via third-party extensions,
might not work anymore as expected.


Affected installations
======================

All installations which use the :code:`$TSFE->JSeventFuncCalls` option, e.g. like GMENU_LAYERS.

Migration
=========

Every call of a 3rd party extension to the mentioned method must be changed to use their own
JS function registration.


.. index:: JavaScript, TypoScript, Frontend
