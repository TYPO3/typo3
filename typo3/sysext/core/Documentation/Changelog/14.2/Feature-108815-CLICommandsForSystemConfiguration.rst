..  include:: /Includes.rst.txt

..  _feature-108815-1738249200:

========================================================
Feature: #108815 - CLI commands for system configuration
========================================================

See :issue:`108815`

Description
===========

New CLI commands have been introduced to manage TYPO3 system configuration
(stored in :file:`config/system/settings.php`) directly from the command line.

The following commands are now available:

configuration:show
------------------

Shows a configuration value. By default, if the active value differs from the
local value (for example, due to overrides in
:file:`config/system/additional.php`), both values are displayed with the
difference highlighted.

..  code-block:: bash

    # Show configuration (with diff if overridden)
    vendor/bin/typo3 configuration:show SYS/sitename

    # Show active (effective runtime) value
    vendor/bin/typo3 configuration:show SYS/sitename --type=active

    # Show local (settings.php) value only
    vendor/bin/typo3 configuration:show DB/Connections/Default --type=local

    # Output as JSON
    vendor/bin/typo3 configuration:show BE/debug --type=active --json

configuration:set
-----------------

Sets a configuration value in :file:`config/system/settings.php`.

..  code-block:: bash

    # Set a string value
    vendor/bin/typo3 configuration:set SYS/sitename "My Site"

    # Set boolean or integer values using --json
    vendor/bin/typo3 configuration:set BE/debug true --json
    vendor/bin/typo3 configuration:set SYS/displayErrors 1 --json

    # Set an array value
    vendor/bin/typo3 configuration:set EXTENSIONS/my_extension '{"key": "value"}' --json

The :bash:`--json` option parses the value as JSON, which allows
booleans, integers, and arrays to be set with proper types.

configuration:remove
--------------------

Removes configuration value or values from :file:`config/system/settings.php`.

..  code-block:: bash

    # Remove a single path (asks for confirmation)
    vendor/bin/typo3 configuration:remove EXTENSIONS/my_extension/setting

    # Remove without confirmation
    vendor/bin/typo3 configuration:remove EXTENSIONS/my_extension/setting --force

    # Remove multiple paths (comma-separated)
    vendor/bin/typo3 configuration:remove "EXTCONF/ext1,EXTCONF/ext2" --force

Impact
======

These commands provide a convenient way to manage TYPO3 system configuration
from the command line, which is especially useful for:

*   automated deployment and provisioning scripts
*   CI/CD pipelines that need to adjust configuration
*   quick configuration changes without needing to access the Install Tool
*   scripting and automation tasks

The commands respect TYPO3 configuration path restrictions and only allow
writing to paths that are defined in the default configuration or explicitly
allowed (such as :php:`EXTENSIONS`, :php:`EXTCONF`, :php:`DB`).

..  index:: CLI, LocalConfiguration, ext:lowlevel
