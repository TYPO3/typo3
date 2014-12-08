=============================================
Changes made in EXT:adodb missing in upstream
=============================================

Now in use
==========
The currently used ADOdb version is 5.19 [1]_.

.. [1] https://github.com/ADOdb/ADOdb/releases/tag/v5.19


Our changes
===========

This is a list of changes we made in ADOdb and may must re-applied if EXT:adodb is
updated to upstream.

- ADOdb: Invalid override method signature (48034_) (Solved in 5.20-dev [2]_)
- ADOdb: Set charset properly (61738_)
- EXT:adodb: Table names in ALTER TABLE broken (63659_)

.. [2] https://github.com/ADOdb/ADOdb/commit/85f05a98974ea85ecae943faf230a27afdbaa746
.. _48034: https://forge.typo3.org/issues/48034
.. _61738: https://forge.typo3.org/issues/61738
.. _63659: https://forge.typo3.org/issues/63659


Diff
====

You'll find a diff file in EXT:adodb/Documentation/typo3-adodb.diff.

