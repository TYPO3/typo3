.. include:: /Includes.rst.txt

=========================================================
Deprecation: #78668 - TypoScript option config.mainScript
=========================================================

See :issue:`78668`

Description
===========

The TypoScript option `config.mainScript` allows to set the frontend entrypoint from "index.php" to something else, and is respected
when links are built, but not when e.g. previewing a page from the backend. This option has been marked as deprecated.


Impact
======

Setting this TypoScript option will trigger a deprecation log entry in the admin panel.


Affected Installations
======================

Any installation using this TypoScript option.

.. index:: TypoScript, Frontend
