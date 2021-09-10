.. include:: ../../Includes.txt

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

Any extension present in the :file:`typo3conf/ext` folder, but not installed by Composer,
will still be considered and marked as part of TYPO3 packages when executing
:shell:`composer install`. The only requirement here is, that such extensions need a
:file:`composer.json` file nonetheless.
Note this behaviour is deprecated and will be removed with TYPO3 v12.

Because all extensions present in the system are considered to be active,
the Extension Manager UI is adapted to not allow changing the active state of
extensions anymore for composer based instances. Respectively the commands
:shell:`extension:activate` and :shell:`extension:deactivate` are disabled in Composer managed
systems as well.

A new command :shell:`extension:setup` is introduced, which supersedes both the extension
manager UI as well as the activate/deactivate commands. It performs all steps that
were performed during activation and deactivation (the active-state is of course not changed).

With the command :shell:`extension:setup` *all* extensions are set up in terms of
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
command :shell:`extension:setup` needs to be executed additionally. :shell:`extension:setup` can and
should also be used, when deploying a TYPO3 project to make sure database schema is up to date.

The Composer root project by default will *not* be recognized as a TYPO3 extension.
For projects that represent a site, this is the desired behavior. However, when
extensions are used as root projects for testing (e.g., for running unit or
functional tests in a CI pipeline), you will need to configure the project so
that the root project also can be recognized as a TYPO3 extension. To achieve this,
please add the following line to the `extra.typo3/cms` section in your extension's
:file:`composer.json`:

.. code-block:: json
    "extra: {
        typo3/cms": {
            "ignore-as-root": false,
            …
        }
     }

The :file:`ext_emconf.php` file of extensions is now obsolete and therefore completely ignored
in Composer based instances. Make sure the information in the :file:`composer.json` file is in
sync with the one in your :file:`ext_emconf.php` file in case you want to provide one for
compatibility with non Composer mode.

.. index:: CLI, Composer, ext:core
