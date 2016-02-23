=============================================
Changes made in EXT:adodb missing in upstream
=============================================

Now in use
==========
The currently used ADOdb version is 5.19 [1]_.

.. [1] https://github.com/ADOdb/ADOdb/releases/tag/v5.19


Our changes
===========

This is a list of changes we made in ADOdb that must re-applied if EXT:adodb is
updated to upstream.

- ADOdb: Invalid override method signature (48034_) (Solved in 5.20-dev [2]_)
- ADOdb: Set charset properly (61738_)
- EXT:adodb: Table names in ALTER TABLE broken (63659_)
- MSSQL native driver for ADOdb returns erroneous message (66674_)
- ADOdb: mssqlnative driver fails to create sequences (66678_)
- ADOdb: mssqlnative driver is not properly initialized (66830_)
- ADOdb: mssqlnative driver does not properly define the port (63070_)
- ADOdb: Allow setting NOT NULL/DEFAULT on blob and text columns (67442_) (Upstream pull request: [3]_)
- ADOdb: Table names in sequences broken (64990_)
- ADOdb: PHP7 redefinition of parameter (71244_)
- Security: XML entity expansion (61269_)

.. [2] https://github.com/ADOdb/ADOdb/commit/85f05a98974ea85ecae943faf230a27afdbaa746
.. [3] https://github.com/ADOdb/ADOdb/pull/118
.. _48034: https://forge.typo3.org/issues/48034
.. _61738: https://forge.typo3.org/issues/61738
.. _63659: https://forge.typo3.org/issues/63659
.. _66674: https://forge.typo3.org/issues/66674
.. _66678: https://forge.typo3.org/issues/66678
.. _66830: https://forge.typo3.org/issues/66830
.. _63070: https://forge.typo3.org/issues/63070
.. _67442: https://forge.typo3.org/issues/67442
.. _64990: https://forge.typo3.org/issues/64990
.. _71244: https://forge.typo3.org/issues/71244
.. _61269: https://forge.typo3.org/issues/61269


Diff
====

You'll find a diff file in EXT:adodb/Documentation/typo3-adodb.diff.

