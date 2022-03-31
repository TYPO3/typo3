.. include:: /Includes.rst.txt

=====================================
Breaking: #79259 - EXT:t3skin removed
=====================================

See :issue:`79259`

Description
===========

The system extension `t3skin` has been removed, as all functionality has been migrated into
other system extensions.

All ExtJS-related images and css files have been moved to EXT:core.

All other images have been unused for a while now, and have been deleted from the TYPO3 core.


Impact
======

Any direct references to EXT:t3skin now lead to missing styling or image(s).


Affected Installations
======================

All installations that use CSS files or images from EXT:t3skin.


Migration
=========

Do not use ExtJS styling or images anymore, as ExtJS will be removed from the core.

Other direct references to image(s) in EXT:t3skin should be migrated to have the image(s) in
custom extension.

.. index:: Backend, TCA, ext:t3skin
