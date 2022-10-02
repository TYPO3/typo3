.. include:: /Includes.rst.txt

.. _breaking-98319-1664641595:

===============================================================================================
Breaking: #98319 - New file location for LocalConfiguration.php and AdditionalConfiguration.php
===============================================================================================

See :issue:`98319`

Description
===========

Every TYPO3 installation requires a mandatory file named :php:`typo3conf/LocalConfiguration.php`.

This file contains system-wide configuration options such as database credentials,
or paths to image processing details.

Historically, the very original name was `typo3conf/localconf.php` (this is also
where the extension's file name `ext_localconf.php` comes from).

This file was renamed in TYPO3 v6.0 to :php:`LocalConfiguration.php`  and since
then returns a PHP array with settings then available in :php:`$TYPO3_CONF_VARS`.

Specific PHP code with additional logic (e.g. context-specific conditions) was
available in :php:`typo3conf/AdditionalConfiguration.php`.

With TYPO3 v12, the names for both files and their location have been changed.

The prefix "Local" in :php:`LocalConfiguration.php` originates from the
three-divided location of "System", "Global" and "Local" extensions - the latter
is "specific to a TYPO3 installation" where as other extensions and configuration
could be shared with multiple TYPO3 installations.

TYPO3 v12 has a strong support for Composer and TYPO3's own code base has
progressed since 2012, when TYPO3 v6.0 was released. TYPO3 Core itself now
only consists of extensions which are available as native Composer packages.
The concept of global extensions has been phased out over the past versions.

Instead, TYPO3 installations now distinguish between "dependencies" such as
custom extensions, TYPO3 Core extensions or extensions from packagist.org or
TYPO3 Extension Repository, and "project-specific" configuration. This
project-specific configuration - as known from other PHP frameworks - is now
placed in a settings configuration file and additional configuration file.

Newcomers or users from other PHP projects might understand the concept of a file
with certain settings much better, so the file locations and the file names
have been changed.

For non-Composer-based installations the file names are:

*   :file:`typo3conf/LocalConfiguration.php` is now available in
    :file:`typo3conf/system/settings.php`
*   :file:`typo3conf/AdditionalConfiguration.php` is now available in
    :file:`typo3conf/system/additional.php`

Composer-based TYPO3 projects by default have the possibility to place certain
files from outside the document root, and using the document root such as :file:`public/`
as a subfolder. This way, Composer-based TYPO3 projects can restrict direct public
access to such files via the webserver.

TYPO3 in its Composer Mode already creates a folder named :file:`config/` on the
project root level, where e.g. site configuration is stored. Within the
:file:`config/` folder, the new location is placed.

*   :file:`typo3conf/LocalConfiguration.php` is now available in
    :file:`config/system/settings.php`
*   :file:`typo3conf/AdditionalConfiguration.php` is now available in
    :file:`config/system/additional.php`

Impact
======

TYPO3 automatically moves :file:`typo3conf/LocalConfiguration.php` and
:file:`typo3conf/AdditionalConfiguration.php` to their respective new places on
the first PHP request. The old file is not evaluated anymore, as soon as the file
in the new location is available.

Affected installations
======================

All TYPO3 installations prior to TYPO3 v12.

Migration
=========

The configuration files are automatically moved with TYPO3 v12.0 to their new
locations, so no manual process is needed.

Projects working with a version control system such as Git might need to adapt
their :file:`.gitignore` file or their deployment strategies.

In addition, TYPO3 projects relying on the file locations and their structures
might need adaptions.

.. index:: LocalConfiguration, PHP-API, NotScanned, ext:core
