.. include:: ../../Includes.txt

===============================================
Breaking: #85398 - Drop documentation extension
===============================================

See :issue:`85398`

Description
===========

Fetching and rendering extension and core manuals directly within
the backend using the documentation extension has been dropped
from the TYPO3 backend.

The module never found broad acceptance and usage in the community,
had various hard to resolve flaws and has been a maintenance burden
for the documentation team ever since.


Impact
======

The Documentation module does not exist anymore and cannot be used
to display manuals in the TYPO3 backend.

The previously required extension "documentation" is not available anymore.

New installations do not have the `documentation` extension installed by default.


Affected Installations
======================

Every TYPO3 instance.


Migration
=========

Current documentation of core functionality, core extensions and
community extensions can always be found on docs_ directly.

Flush all TYPO3 Core Caches to ensure that :php:`PackageStates.php` is rebuilt
without the documentation extension.

For composer installations, ensure that the dependency to `typo3/cms-documentation` is removed.

Extensions authors need to ensure that dependencies to `EXT:documentation` are removed, if
they existed before.

.. _docs: https://docs.typo3.org

.. index:: NotScanned
