.. include:: /Includes.rst.txt

======================================================================
Feature: #94996 - Consider all Composer installed extensions as active
======================================================================

See :issue:`94996`

Description
===========

All TYPO3 extensions installed with Composer are now considered to be active and
therefore can and will interact with TYPO3 API.

At Composer install time a persistent artifact is created, holding the information
which extensions are installed and the path where these reside. This makes
the :file:`typo3conf/PackageStates.php` file obsolete and it is neither created nor
evaluated anymore.

For Composer based installs the artifact is located at
:file:`vendor/typo3/PackageArtifact.php`. This file must be deployed
together with all other Composer dependencies. In a TYPO3v11 sprint this file was located at
:file:`var/build/PackageArtifact.php` which did need a special handling and caused
some issues for example on platform.sh, which were solved by storing it in the vendor folder.

Any extension present in the :file:`typo3conf/ext` folder, but not installed by Composer,
will still be considered and marked as part of TYPO3 packages when executing
:bash:`composer install`. The only requirement here is, that such extensions need a
:file:`composer.json` file nonetheless.
Note this behaviour is deprecated and will be removed with TYPO3 v12.

Because all extensions present in the system are considered to be active,
the Extension Manager UI is adapted to not allow changing the active state of
extensions anymore for composer based instances. Respectively the commands
:bash:`extension:activate` and :bash:`extension:deactivate` are disabled in Composer managed
systems as well.

A new command :bash:`extension:setup` is introduced, which supersedes both the extension
manager UI as well as the activate/deactivate commands. It performs all steps that
were performed during activation and deactivation (the active-state is of course not changed).

With the command :bash:`extension:setup` *all* extensions are set up in terms of
database schema changes, static data import, distribution files imports, etc.
As example, requiring an additional extension and then using this command will
create database tables or additional database fields the extension provides.

Any installed Composer package that defines an `extra.typo3/cms` section in
their :file:`composer.json` file will be considered a TYPO3 extension and will
have full access to the TYPO3 API.

However, because these Composer packages reside in the :file:`vendor` folder, they can
not deliver public resources. This remains exclusive for TYPO3 extensions
installed into :file:`typo3conf/ext` for now - those composer packages that not only
have a `extra.typo3/cms` section, but are also of type `typo3-cms-extension`.

Impact
======

In Composer mode this has the following impact:

The :file:`PackageStates.php` file is completely ignored. When migrating projects that
still have this file e.g. under version control, it is recommended to remove this file.

Projects with extensions that reside directly in :file:`typo3conf/ext`, and which therefore
are not installed with Composer, should consider migrating them to a local path repository.
In any case, such extensions now require to have a :file:`composer.json` file. This file
can be created by using the according UI in the Extension Manager.

When working on a Composer based project and adding new extensions via the Composer
cli tool during development, all added extensions are considered active automatically,
but are not yet set up in terms of database schema changes for example. The TYPO3 cli
command :bash:`extension:setup` needs to be executed additionally. :bash:`extension:setup` can and
should also be used, when deploying a TYPO3 project to make sure database schema is up to date.

The Composer root project package will be recognized as a TYPO3 extension as well, if it provides a
`extra.typo3/cms` section in its `composer.json`, as mentioned above. Because this package,
like packages in the `vendor` folder isn't accessible by the web server,
the root package can not deliver public resources as well.

However, when extensions are used as root package for testing (e.g., for running unit,
functional or integration tests in a CI pipeline) **and** these extensions have files in the `Resources/Public` directory,
a symlink in the `typo3conf/ext` directory is automatically created.
Additionally the package path is adapted to be inside `typo3conf/ext`.
This allows TYPO3 to properly calculate URLs for public resources of this extension.

If the root package isn't of type `typo3-cms-extension` or does not have a `Resources/Public` directory
the absolute path to the extension remains the original path to the composer root directory
and no symlink is created.

This special behaviour for root packages of type `typo3-cms-extension`
is introduced as a temporary fix to ease extension testing. It is explicitly **NOT**
recommended to use such a setup in production.

The :file:`ext_emconf.php` file of extensions is now obsolete and therefore completely ignored
in Composer based instances. Make sure the information in the :file:`composer.json` file is in
sync with the one in your :file:`ext_emconf.php` file in case you want to provide one for
compatibility with non Composer mode.

.. index:: CLI, Composer, ext:core
