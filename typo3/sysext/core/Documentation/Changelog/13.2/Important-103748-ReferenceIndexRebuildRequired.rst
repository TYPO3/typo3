.. include:: /Includes.rst.txt

.. _important-103748-1714290385:

=====================================================
Important: #103748 - Reference index rebuild required
=====================================================

See :issue:`103748`

Description
===========

A series of new columns has been added to the reference index table
:sql:`sys_refindex`. This requires a rebuild of the table. All instances
must update the reference index when upgrading.

In TYPO3 v13 the reference index has become more important. Most notably,
it is used in the frontend for performance improvements. This requires
a valid index and keeping it up-to-date after deployments is mandatory
to avoid incorrect data during frontend and backend processing.

After deployment and initial rebuild, the index is kept up-to-date automatically
by the :php:`DataHandler` when changing records in the backend .

In general, updating the reference index is required when database
relations that are defined in TCA change - typically when adding, removing
or changing extensions, and after TYPO3 Core updates (also patch level).

It is strongly recommended to update the reference index after deployments.
Note TYPO3 v13 has optimized this operation - a full update is usually much
quicker than with previous versions.

The recommended way to rebuild and fully update the reference index is the CLI
command:

.. code-block:: bash

    bin/typo3 referenceindex:update

If CLI can not be used, the reference index can be updated in the backend
using the "DB check" module in the "typo3/cms-lowlevel" extension. Since
the update process may take a while, PHP web processes may time out during
this operation, which makes this backend interface suitable for small sized
instances only.


.. index:: Database, ext:core
