.. include:: /Includes.rst.txt

.. _breaking-98437-1664187644:

==========================================================================
Breaking: #98437 - Workspace TSConfig swapMode and changeStageMode removed
==========================================================================

See :issue:`98437`

Description
===========

The following User TSconfig options related to the system extension "workspaces"
have been removed.

* :typoscript:`options.workspaces.swapMode`
* :typoscript:`options.workspaces.changeStageMode`

Impact
======

Setting the above options to :typoscript:`any` or :typoscript:`page` in
User TSconfig has no effect anymore: They were used to publish and change
state of more than the selected records in the workspace Backend module, which
was a rather hard to grasp feature for editors and usability wise questionable.

Affected installations
======================

Instances with loaded workspaces extensions using these options in
User TSconfig are affected.

These options were most likely used very seldom and the implementation has
been at least partially broken since TYPO3 Core v8.

Migration
=========

No migration path available.

.. index:: Backend, TSConfig, NotScanned, ext:workspaces
