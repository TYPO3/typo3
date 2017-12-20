
.. include:: ../../Includes.txt

===========================================
Breaking: #67824 - typo3/ext folder removed
===========================================

See :issue:`67824`

Description
===========

The folder `typo3/ext` does not exist in the default core package anymore.
The functionality to have global extensions in this directory is not touched.


Impact
======

In case global extensions are moved to this directory during
deployment or rollout, the directory must be created before, now.


Affected Installations
======================

Instances that use global extensions within `typo3/ext`.


Migration
=========

Create directory `typo3/ext` before moving extensions into this folder.


.. index:: PHP-API
