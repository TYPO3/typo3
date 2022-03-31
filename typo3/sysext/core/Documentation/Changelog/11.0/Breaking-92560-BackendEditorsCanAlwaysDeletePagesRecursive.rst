.. include:: /Includes.rst.txt

====================================================================
Breaking: #92560 - Backend editors can always delete pages recursive
====================================================================

See :issue:`92560`

Description
===========

The feature to deny editors from deleting pages that have sub pages has been
removed. This has been an optional setting on a per-user basis and is now not
only enabled by default but the restriction has been fully removed.


Impact
======

Editors can always delete full page trees.


Affected Installations
======================

All instances are affected.


Migration
=========

In case an editor deletes an entire tree by accident, administrators can and
should use the recycler extension to resurrect page trees.

Additionally, administrators can and should set access rights of important key
pages to disallow editors from deleting them. More complex use cases can be
handled with a dedicated DataHandler hook.

Another good solution is to configure and restrict users to a workspace to
implement a sophisticated review process for pending live content changes.

On PHP level, the property :php:`DataHandler->deleteTree` has been dropped.
Setting this property will raise a PHP warning level error. Extensions may be
affected by this. The extension scanner will find usages with a weak match.

Furthermore, on PHP level, the backend user uc setting :php:`uc['recursiveDelete']`
has been dropped and is of no use anymore within the TYPO3 core.

Finally, the User TSconfig for setting a default value, overriding the value or
disabling the field, has also no effect anymore. Therefore, the following
settings within custom TSconfig should be removed:

* :typoscript:`setup.default.recursiveDelete`
* :typoscript:`setup.override.recursiveDelete`
* :typoscript:`setup.fields.recursiveDelete.disabled`

.. index:: Backend, PHP-API, PartiallyScanned, ext:backend
