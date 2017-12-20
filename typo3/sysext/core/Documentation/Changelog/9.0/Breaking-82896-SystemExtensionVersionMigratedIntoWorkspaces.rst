.. include:: ../../Includes.txt

========================================================================
Breaking: #82896 - System extension "version" migrated into "workspaces"
========================================================================

See :issue:`82896`

Description
===========

The basic functionality of versioning records, previously located within the "version" system
extension was moved into the "workspaces" extension, which not only enhances the versioning with
workflows and workflow stages, but also adds a Backend module to configure and to publish versioned
records within a workspace.

The extensions' deeply coupled logic is now moved into one system extension, providing the same
functionality still.


Impact
======

Using the versioning functionality of TYPO3 is now coupled with the workspace and workflow logic,
and cannot be used separately for custom versioning strategies not supported by TYPO3 Core.

Additionally, third-party extensions checking for the previously available "version" extensions
will trigger a deprecation warning.


Affected Installations
======================

Any installation solely providing versioning functionality based on the "version" extension,
but not using "workspaces".


Migration
=========

Adapt your changes to check for "workspaces" instead of the "version" extension.

.. code-block:: php

	# old
	if (ExtensionManagementUtility::isLoaded('version')) { ... }

	# new
	if (ExtensionManagementUtility::isLoaded('workspaces')) { ... }

If you built custom functionality built on "version" without "workspaces", ensure to adapt
your settings and old class names to use the workspace PHP namespaces.

.. index:: PHP-API, NotScanned, ext:workspaces