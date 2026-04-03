.. include:: /Includes.rst.txt

.. _feature-108345-1774117214:

==========================================================================
Feature: #108345 - Allow extensions without ext_emconf.php in classic mode
==========================================================================

See :issue:`108345`

Description
===========

Initially, `ext_emconf.php` was the only file used to provide
extension metadata. With the introduction of `composer.json`,
which recently became mandatory for extensions,
there were now two files containing a lot of redundant data.

This is now resolved by allowing an extension's `composer.json`
to contain information that previously had to be defined in
`ext_emconf.php`:

1. Extension title
2. Extension version
3. Dependencies on other TYPO3 extensions

Extension title
---------------

See :ref:`feature-108653-1767199420` for how the extension title can also be set
in `composer.json`.

Extension version
-----------------

The version number can be set in `extra.typo3/cms.version` or alternatively
in the regular `"version"` field in `composer.json`.

For third-party extensions to be compatible with TYPO3 classic mode,
this version must now be set to the exact version previously defined in `ext_emconf.php`
and must match the version in the Git tag (for example, when publishing to Packagist).

Fixture extensions used in tests can set any version number, for example `1.0.0`,
but a version number must still be provided to avoid deprecation messages.
During testing, the version number is not evaluated.

TYPO3 Core extensions may omit the version number
in `composer.json`, because their version can and will be derived from
php`\TYPO3\CMS\Core\Information\Typo3Version`.

Dependencies on other TYPO3 extensions
--------------------------------------

`ext_emconf.php` had a property for specifying dependencies
on other extensions by referencing the extension key and, optionally,
a version range.

`composer.json` also contains a field for specifying dependencies
using a Composer package name with a version range.
However, there is no direct way to distinguish whether such a package name
refers to another TYPO3 extension or to a regular Composer package
that should be installed from Packagist.

TYPO3, however, needs to know which other extensions an extension depends on
in order to resolve the extension loading order correctly.

Therefore, TYPO3 must know which package names refer to extensions
and which do not. In Composer mode, this can be resolved automatically.

In classic mode, extensions must specify which package names
in the `require` section of `composer.json` are regular Composer packages.
All other package names in the `require` section are assumed
to be TYPO3 extensions.

Because of this, extension authors who want their extensions
to be compatible with TYPO3 classic mode **and** whose extensions
have dependencies on Composer packages **must**
also specify which Composer packages they provide when loaded
in classic mode.

This is done by specifying which Composer packages the extension
provides in classic mode.

Here is an example:

Before:

..  code-block:: json

    {
        "name": "vendor/example",
        "type": "typo3-cms-extension",
        "description": "example",
        "license": "GPL-2.0-or-later",
        "require": {
            "typo3/cms-core": "^14.2",
            "vendor/other-example": "*",
            "symfony/dotenv": "^8.0"
        },
        "extra": {
            "typo3/cms": {
                "extension-key": "example_extension"
            }
        }
    }

After:

..  code-block:: json

    {
        "name": "vendor/example",
        "type": "typo3-cms-extension",
        "description": "example",
        "license": "GPL-2.0-or-later",
        "require": {
            "typo3/cms-core": "^14.2",
            "vendor/other-example": "*",
            "symfony/dotenv": "^8.0"
        },
        "extra": {
            "typo3/cms": {
                "extension-key": "example_extension",
                "version": "1.0.0",
                "Package": {
                    "providesPackages": {
                        "symfony/dotenv": ""
                    }
                }
            }
        }
    }

In this example, the Composer package names `typo3/cms-core` and `vendor/other-example`
are assumed to refer to TYPO3 extensions, and TYPO3 guarantees that `vendor/example`
is loaded after `vendor/other-example`. Otherwise, an error is thrown if
the extension `vendor/other-example` does not exist in the system.

As before, extensions still need to find an appropriate way to
ship Composer packages and determine when to require the autoloader for them.

Even if an extension does not depend on any Composer packages, it is still
**required** to specify `providesPackages` in `composer.json`
to ensure future compatibility with TYPO3 classic mode and to avoid
deprecation messages in TYPO3 v14.

..  code-block:: json

    {
        "name": "vendor/example",
        "type": "typo3-cms-extension",
        "description": "example",
        "license": "GPL-2.0-or-later",
        "require": {
            "typo3/cms-core": "^14.2",
            "vendor/other-example": "*"
        },
        "extra": {
            "typo3/cms": {
                "extension-key": "example_extension",
                "version": "1.0.0",
                "Package": {
                    "providesPackages": {}
                }
            }
        }
    }

If the `version` field is set anyway, the version is populated from there
and `extra.typo3/cms.version` can be omitted:

..  code-block:: json

    {
        "name": "vendor/example",
        "version": "1.0.0",
        "type": "typo3-cms-extension",
        "description": "example",
        "license": "GPL-2.0-or-later",
        "require": {
            "typo3/cms-core": "^14.2",
            "vendor/other-example": "*",
            "symfony/dotenv": "^8.0"
        },
        "extra": {
            "typo3/cms": {
                "extension-key": "example_extension",
                "Package": {
                    "providesPackages": {
                        "symfony/dotenv": ""
                    }
                }
            }
        }
    }

Be aware that keeping `ext_emconf.php`, while no longer directly required
by TYPO3, may still be necessary for some tools,
such as Tailor or TYPO3 TER. Therefore, for the time being, it is recommended
to keep the file and ensure that its information stays in sync
with `composer.json` as outlined above.

However, TYPO3 will **not** evaluate `ext_emconf.php` anymore if the `version` field
and `providesPackages` are correctly defined in `composer.json`.

Impact
======

Extensions can now omit `ext_emconf.php` in TYPO3 classic mode.
A deprecation message is shown during cache warm-up when `ext_emconf.php`
is present and `composer.json` is not yet future-proof
because it does not contain the `version` and `providesPackages` definitions.

.. index:: ext:core
