
.. include:: /Includes.rst.txt

===================================================================
Feature: #68589 - Add CLI command to dump class loading information
===================================================================

See :issue:`68589`

Description
===========

In rare cases it is useful to update the additional class loading information TYPO3 writes in non composer mode.
We now provide a CLI command which does that.


Impact
======

By executing `typo3/cli_dispatch.phpsh extbase extension:dumpclassloadinginformation` on the command line,
the class loading information of all active extensions is updated.


.. index:: CLI
