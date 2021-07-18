.. include:: ../../Includes.txt

====================================================================
Deprecation: #82909 - TypoScript option config.typolinkCheckRootline
====================================================================

See :issue:`82909`

Description
===========

The TypoScript option :typoscript:`config.typolinkCheckRootline` is now always active.


Impact
======

Setting this option in TypoScript setup will trigger a deprecation warning.


Affected Installations
======================

Any installation having this option set.


Migration
=========

Just remove the TypoScript instruction, as it is not needed anymore.

.. index:: TypoScript, Frontend, NotScanned
