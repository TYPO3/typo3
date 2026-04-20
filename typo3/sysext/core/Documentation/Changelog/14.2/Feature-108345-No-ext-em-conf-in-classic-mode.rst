..  include:: /Includes.rst.txt

..  _feature-108345-1774117214:

==========================================================================
Feature: #108345 - Allow extensions without ext_emconf.php in classic mode
==========================================================================

See :issue:`108345`

Description
===========

Initially :file:`ext_emconf.php` was the only file providing
extension metadata. Since the introduction of :file:`composer.json`,
now mandatory for extensions,
there are now two files containing a lot of redundant data.

This is now resolved by allowing an extension's :file:`composer.json`
to contain information that was previously defined in
:file:`ext_emconf.php`:

1. Extension title and description
2. Extension version
3. Extension state / update exclusion
4. Dependencies on other TYPO3 extensions
5. PHP version constraints

Extension title and description
-------------------------------

See :ref:`feature-108653-1767199420` for how the extension title and description can be set
individually in :file:`composer.json`.

Extension version
-----------------

The version number can be set in `extra.typo3/cms.version` or alternatively
in the `"version"` field in :file:`composer.json`.

For third-party extensions to be compatible with TYPO3 classic mode,
this version must now be set to the same version previously defined in :file:`ext_emconf.php`
and should match the version in the Git tag, for example when publishing to Packagist.

Fixture extensions used in tests can set any version number, for example `1.0.0`,
but a version number must still be provided to avoid deprecation messages.
During testing the version number is not evaluated.

TYPO3 Core extensions may omit the version number
in :file:`composer.json` because their version number is derived via
:php:`TYPO3\CMS\Core\Information\Typo3Version`.

Extension state and update exclusion
------------------------------------

The former `state` property in :file:`ext_emconf.php` was used for multiple purposes.
In :file:`composer.json`, this is now represented by dedicated metadata instead
of a single field.

Supported extension stability values are expressed as version suffixes, for example:

..  code-block:: json

    {
        "name": "vendor/example",
        "type": "typo3-cms-extension",
        "description": "Example extension",
        "extra": {
            "typo3/cms": {
                "extension-key": "example_extension",
                "version": "1.2.3-alpha4",
                "Package": {
                    "providesPackages": {}
                }
            }
        }
    }

Supported Composer stability values are:

*   `dev`
*   `alpha`
*   `beta`
*   `RC`
*   `stable`

For example:

*   `1.2.3-dev`
*   `1.2.3-alpha1`
*   `1.2.3-beta2`
*   `1.2.3-RC3`
*   `1.2.3`

Values from the former `state` field that are not supported by Composer stability
can be expressed as build metadata by appending `+...` to the version string.

Example:

..  code-block:: json

    {
        "name": "vendor/example",
        "type": "typo3-cms-extension",
        "description": "Example extension",
        "extra": {
            "typo3/cms": {
                "extension-key": "example_extension",
                "version": "1.4.2+obsolete",
                "Package": {
                    "providesPackages": {}
                }
            }
        }
    }

In this example, TYPO3 will treat the version as `1.0.0`, keep `obsolete`
as build metadata, and expose it in the Extension Manager.

The former `state = excludeFromUpdates` value from :file:`ext_emconf.php`
is now represented by a dedicated boolean flag in :file:`composer.json`:

..  code-block:: json

    {
        "name": "vendor/example",
        "type": "typo3-cms-extension",
        "description": "Example extension",
        "extra": {
            "typo3/cms": {
                "extension-key": "example_extension",
                "version": "1.2.3",
                "exclude-from-updates": true,
                "Package": {
                    "providesPackages": {}
                }
            }
        }
    }

This replaces overloading the former `state` field for update handling.

Dependencies on other TYPO3 extensions
--------------------------------------

:file:`ext_emconf.php` had a property for specifying dependencies
on other extensions by referencing the extension key and an optional
range of versions.

:file:`composer.json` also contains a field for specifying dependencies
using a Composer package name with a version range.
However, there is no direct way to distinguish whether such a package name
refers to another TYPO3 extension or to a regular Composer package
that should be installed from Packagist.

TYPO3, however, needs to know which other extensions an extension depends on
in order to resolve the extension loading order correctly.

Therefore, TYPO3 must know which package names refer to TYPO3 extensions
and which refer to regular Composer packages. In Composer mode, this can
be resolved automatically.

In classic mode, TYPO3 now recognizes several categories:

* TYPO3 framework packages shipped by the core
* Composer packages already installed and shipped with TYPO3
* Composer packages provided by other loaded extensions via
  `providesPackages`

Because of this, extension authors do not need to repeat such package names
in `providesPackages`.

Extensions still need to declare Composer packages that they themselves provide
when loaded in classic mode. For those entries, `providesPackages` can also
define a relative path to a Composer vendor directory. If that directory contains
a Composer-generated `autoload.php`, TYPO3 includes it early during bootstrap.

This makes it possible to both declare Composer packages and bootstrap
their autoloader in a standardized way.

Here is an example of an extension that ships a local Composer vendor directory:


..  code-block:: json

    {
        "name": "vendor/example",
        "type": "typo3-cms-extension",
        "description": "Example extension",
        "license": "GPL-2.0-or-later",
        "require": {
            "typo3/cms-core": "^14.2",
            "vendor/other-example": "*",
            "symfony/dotenv": "^8.0"
        },
        "extra": {
            "typo3/cms": {
                "extension-key": "example_extension",
                "version": "1.2.3",
                "Package": {
                    "providesPackages": {
                        "symfony/dotenv": "Resources/Private/Php/ComposerVendor"
                    }
                }
            }
        }
    }


In this example, the package `symfony/dotenv` is provided by the extension itself
in TYPO3 classic mode, and TYPO3 will include
`Resources/Private/Php/ComposerVendor/autoload.php` early if it is a
Composer-generated autoload file.

The Composer package names `typo3/cms-core` and `vendor/other-example`
are assumed to refer to TYPO3 extensions, and TYPO3 guarantees that `vendor/example`
is loaded after `vendor/other-example`. Otherwise, an error is thrown if
the extension `vendor/other-example` does not exist in the system.

Packages that are already shipped by TYPO3 or already provided by another loaded
extension do not need to be listed in `providesPackages`.

Even if an extension does not depend on any Composer packages,
it is still **required** to specify `providesPackages` in :file:`composer.json`
as an empty object to ensure future compatibility with TYPO3 classic mode
and to avoid deprecation messages in TYPO3 v14.

..  code-block:: json

    {
        "name": "vendor/example",
        "type": "typo3-cms-extension",
        "description": "Example extension",
        "license": "GPL-2.0-or-later",
        "require": {
            "typo3/cms-core": "^14.2",
            "vendor/other-example": "*"
        },
        "extra": {
            "typo3/cms": {
                "extension-key": "example_extension",
                "version": "1.2.3",
                "Package": {
                    "providesPackages": {}
                }
            }
        }
    }

PHP version constraints
-----------------------

PHP version constraints from :file:`ext_emconf.php` can also be represented in
the `require` section of :file:`composer.json`.

Example:

..  code-block:: json

    {
        "name": "vendor/example",
        "type": "typo3-cms-extension",
        "description": "Example extension",
        "require": {
            "typo3/cms-core": "^14.2",
            "php": "^8.2"
        },
        "extra": {
            "typo3/cms": {
                "extension-key": "example_extension",
                "version": "1.5.6",
                "Package": {
                    "providesPackages": {}
                }
            }
        }
    }

The PHP dependency is kept as package metadata so TYPO3 classic mode
can still evaluate PHP version requirements. However, it is ignored for
extension dependency ordering.

Be aware that keeping :file:`ext_emconf.php`, while no longer directly required
by TYPO3, may still be necessary for some tools,
such as Tailor or TYPO3 TER. Therefore, for the time being, it is recommended
to keep the file and ensure that its information stays in sync
with :file:`composer.json` as outlined above.

However, TYPO3 will **not** evaluate :file:`ext_emconf.php` anymore if the required
metadata is correctly defined in :file:`composer.json` and package metadata can be
derived from it.

Impact
======

Extensions can now omit :file:`ext_emconf.php` in TYPO3 classic mode.
A deprecation message is shown during cache warm-up when :file:`ext_emconf.php`
is present and :file:`composer.json` is not yet future-proof
because it does not contain the required metadata definitions.

..  index:: ext:core
