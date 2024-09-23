.. include:: /Includes.rst.txt

.. _important-103748-1714290385:

=====================================================
Important: #103748 - Reference index rebuild required
=====================================================

See :issue:`103748`

Description
===========

A series of new columns has been added to the reference index table
:sql:`sys_refindex`. This requires a rebuild of this table. All instances
must update the reference index when upgrading.

The reference index becomes more important with TYPO3 v13: Most notably, it
is used within the frontend for structural performance improvements. This requires
a valid index and keeping it up-to-date after deployments becomes mandatory
to avoid wrong data during frontend and backend processing.

After deployment and initial rebuild, the index is kept up-to-date when
changing records in the backend by the :php:`DataHandler` automatically.

In general, fully updating the reference index is required when database
relations defined in TCA change - typically when adding, removing
or changing extensions, and after TYPO3 Core updates (also patch level).

It is strongly recommended to update the reference index after deployments.
Note TYPO3 v13 optimized this operation, a full update is usually much
quicker than with previous versions.

The typical and recommended way to rebuild and fully update the
reference index is this CLI command:

..  code-block:: bash

    bin/typo3 referenceindex:update

If CLI can not be used, the backend allows updating the reference index
using the "DB check" module of the "typo3/cms-lowlevel" extension. With
the update process taking a while, PHP web processes may time out during
this operation, which makes this backend interface suitable for small sized
instances only.


.. index:: Database, ext:core
