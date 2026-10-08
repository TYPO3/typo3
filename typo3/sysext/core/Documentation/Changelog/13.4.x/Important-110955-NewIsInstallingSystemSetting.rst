..  include:: /Includes.rst.txt

..  _important-110955-1791395981:

=============================================================
Important: #110955 - New global system setting "isInstalling"
=============================================================

See :issue:`110955`

Description
===========

A new global boolean configuration option
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['isInstalling']` has been introduced.
It indicates whether a first installation is currently in progress and controls
whether the TYPO3 installer (the first-install wizard, not the Install Tool /
Admin Tools) may run on an instance that already has a
:file:`config/system/settings.php`.

The installer is now only reachable when one of the following is true:

*   no :file:`settings.php` exists yet (a brand-new instance), or
*   :php:`SYS/isInstalling` is set to :php:`true`.

The value defaults to :php:`false`. A fresh installation automatically sets it to
:php:`true` while the wizard runs and back to :php:`false` once the installation is
finished. Instances upgraded from a TYPO3 version that did not yet know this
option are treated as not installing, so the installer is not reachable for them.

As a consequence, placing a :php:`FIRST_INSTALL` marker file in the public web root
no longer re-opens the installer on an already configured instance. If you
intentionally need to (re-)run the installer on an instance that still has a
:file:`settings.php` (for example when re-provisioning), set
:php:`SYS/isInstalling` to :php:`true` in :file:`settings.php` first. Changing this
value requires file system access and can therefore not be triggered through the
web.

..  index:: LocalConfiguration, ext:install
