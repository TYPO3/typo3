.. include:: ../../Includes.txt

==============================================================================
Feature: #84545 - Allow temporary files to be stored outside the document root
==============================================================================

See :issue:`84545`

Description
===========

The environment variable called `TYPO3_PATH_APP`, which was previously introduced with the Environment API, is now used
to allow to store data outside of the document root.

All regular composer-based installations now benefit from this functionality directly, as data which was previously
stored and hard-coded within :file:`typo3temp/var/` is now stored within the **project root** folder :file:`var/`.

For non-composer installations, it is possible to set the environment variable to a folder usually one level
upwards than the regular **web root**. This increases security for any TYPO3 installation as files are not
publicly accessible (for example via web browser) anymore.

A typical example:
- `TYPO3_PATH_APP` is set to :file:`/var/www/my-project`.
- The web folder is then set to `TYPO3_PATH_ROOT` :file:`/var/www/my-project/public`.

Non-public files are then put to
- :file:`/var/www/my-project/var/session` (like Maintenance Tool Session files)
- :file:`/var/www/my-project/var/cache` (Caching Framework data)
- :file:`/var/www/my-project/var/lock` (Files related to locking)
- :file:`/var/www/my-project/var/log` (Files related to logging)
- :file:`/var/www/my-project/var/extensionmanager` (Files related to extension manager data)
- :file:`/var/www/my-project/var/transient` (Files related to import/export, core updater, FAL)

If the option is not set, the :file:`typo3temp/var/` folder is still used, but with some minor differences
regarding the naming scheme of the folders.


Impact
======

For installations having the environment variable set, the folder is now not within :file:`typo3temp/var/` anymore
but outside of the document root in a folder called :file:`var/`.

For installations without this setting in use, there are minor differences in the folder structure:
- :file:`typo3temp/var/cache` is now used instead of :file:`typo3temp/var/Cache`
- :file:`typo3temp/var/log` is now used instead of :file:`typo3temp/var/log`
- :file:`typo3temp/var/lock` is now used instead of :file:`typo3temp/var/locks`
- :file:`typo3temp/var/session` is now used instead of :file:`typo3temp/var/InstallToolSessions`
- :file:`typo3temp/var/extensionmanager` is now used instead of :file:`typo3temp/var/ExtensionManager`

Although it is a most common understanding in the TYPO3 world that `typo3temp/` can be removed at any time,
it is considered bad practice to remove the whole folder. Only folders relevant for the current development
changes should selectively be removed.

.. index:: CLI, PHP-API
