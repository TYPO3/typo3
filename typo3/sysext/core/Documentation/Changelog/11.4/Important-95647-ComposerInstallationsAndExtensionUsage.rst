.. include:: /Includes.rst.txt

==============================================================
Important: #95647 - Composer installations and extension usage
==============================================================

See :issue:`95647`

Description
===========

With :issue:`94996` the behavior for Composer-based installations has changed.

Importance of :file:`ext_emconf.php` file
-----------------------------------------

The :file:`ext_emconf.php` file which is located in the extensions' base folder,
is not evaluated anymore in Composer-based installations. This means, the
ordering of the extensions and their dependencies are now loaded from the
:file:`composer.json` file, instead of :file:`ext_emconf.php`.

For non-Composer installation ("Classic Mode") the `ext_emconf.php` file is the
source of truth for required dependencies and the loading order of active
extensions.

Extension authors should ensure that the information in the :file:`composer.json`
file is in sync with the one in the extensions' :file:`ext_emconf.php` file.
This is especially important regarding constraints like `depends` , `conflicts`
and `suggests`. Use the equivalent settings in :file:`composer.json` `require`,
`conflict` and `suggest` to set dependencies and ensure a specific loading order.

It is recommended to keep :file:`ext_emconf.php` and :file:`composer.json` in
any public extension that is published to TYPO3 Extension Repository (TER), and
to ensure optimal compatibility with Composer-based installations and Classic
mode.

Removal of :file:`PackageStates.php`
------------------------------------

The :file:`typo3conf/PackageStates.php` file is not evaluated anymore in
Composer-based installations. When updating TYPO3 installations that still
contain this file e.g. under version control, the file can safely be removed.

Use the TYPO3 CLI command :bash:`extension:setup` to set up all extensions
available in Composer.

Package information (like paths or extension meta data) is still stored in and evaluated from
a file in Composer's :file:`vendor` folder. This file is written after Composer dumps autoload information.
Make sure all files from that (:file:`vendor`) folder are transferred during a deployment.
This means no special action compared to previous TYPO3 versions is required regarding the :file:`vendor` folder
with TYPO3 11 LTS.

.. Important::
   TYPO3 version 11.5.0 to 11.5.2 stored package information in :file:`var/build/` folder,
   which previously required this folder to be transferred as well during a deployment.
   This is not required any more now. Transferring the :file:`vendor` folder is sufficient now.

All extensions are always active
--------------------------------

All extensions and their dependant extensions required via Composer in a
Composer-based TYPO3 installation are **always** activated. It is not possible
to disable an extension by using the Extension Manager anymore.

The TYPO3 CLI command :bash:`extension:setup` can be used after each
`composer require` or `composer update` command to update the database schema
and other important actions usually done when previously activating an extension
in the Extension Manager.

.. index:: Backend, Frontend, ext:core
