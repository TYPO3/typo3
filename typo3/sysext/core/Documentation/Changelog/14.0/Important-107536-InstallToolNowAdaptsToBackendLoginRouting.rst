..  include:: /Includes.rst.txt

..  _important-107536-1718204204:

=====================================================================
Important: #107536 - Install Tool now adapts to backend login routing
=====================================================================

See :issue:`107536`

Description
===========

The Install Tool now integrates with TYPO3's backend routing system instead of
using a separate :file:`typo3/install.php` file. This modernization improves
consistency while maintaining full backward compatibility.

If the TYPO3 installation is not working properly, the Install Tool can now be
accessed via the `?__typo3_install` parameter behavior so that administrators
rely on for system maintenance and recovery.

Impact
======

**For Administrators:**

All existing workflows continue to work without changes. However, the Install
Tool is now accessible via:

- The `__typo3_install` parameter (e.g., `https://example.com/?__typo3_install`)
- Backend routes like `/typo3/install` and `/typo3/install.php` keep working,
  if the :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['entryPoint']` is set, the
  Install Tool adapts accordingly.

**System Recovery:**

When TYPO3 installation is not set up or not working properly, the magic
`__typo3_install` parameter still redirects to the installer/maintenance tool
as before, ensuring administrators can always access system recovery tools.

**Technical Benefits:**

The Install Tool now uses the same routing infrastructure as the rest of the
TYPO3 backend, creating a more unified and maintainable architecture while
supporting the long-term goal of simplifying TYPO3's directory structure.

Migration
=========

No migration is required. All existing documentation, scripts, and workflows
using the Install Tool continue to function without modification.

..  index:: Backend, Install, ext:install
