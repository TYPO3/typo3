..  include:: /Includes.rst.txt

..  _deprecation-108345-1774126701:

====================================================
Deprecation: #108345 - Deprecation of ext_emconf.php
====================================================

See :issue:`108345`

Description
===========

TYPO3 extensions that still ship an `ext_emconf.php` file
**and** do not declare future compatibility to omit this file
will now trigger a deprecation message during cache warm-up.

To avoid this deprecation message, the extension must provide
the required package metadata in `composer.json`.

At minimum, this includes the extension version and the
`providesPackages` definition:

..  code-block:: json
    :caption: composer.json for an extension providing Composer packages

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
                "version": "1.0.0",
                "Package": {
                    "providesPackages": {
                        "symfony/dotenv": "Resources/Private/Php/ComposerVendor"
                    }
                }
            }
        }
    }

..  code-block:: json
    :caption: composer.json for an extension not providing Composer packages

    {
        "name": "vendor/example2",
        "type": "typo3-cms-extension",
        "description": "Example extension",
        "license": "GPL-2.0-or-later",
        "require": {
            "typo3/cms-core": "^14.2"
        },
        "extra": {
            "typo3/cms": {
                "extension-key": "example2_extension",
                "version": "1.0.0",
                "Package": {
                    "providesPackages": {}
                }
            }
        }
    }

For compatibility with TYPO3 classic mode, third-party extensions
must set the exact extension version in `extra.typo3/cms.version`
or in the top level `version` field of :file:`composer.json`.
This version must match the version previously
defined in :file:`ext_emconf.php` and the released Git tag.

Fixture extensions used in tests can set any version number, for example `1.0.0`,
but a version number must still be provided to avoid deprecation messages.

During testing, the version number is not evaluated.

TYPO3 Core extensions may omit the version number
in :file:`composer.json` because their version number is derived from
:php-short:`\TYPO3\CMS\Core\Information\Typo3Version`.

State migration
---------------

The former `state` field from `ext_emconf.php` is deprecated as a source of
extension metadata and should be set in `composer.json` using
dedicated metadata instead.

Supported stability values should be expressed via the version string:

..  code-block:: json
    :caption: composer.json using version stability suffixes

    {
        "name": "vendor/example",
        "type": "typo3-cms-extension",
        "description": "Example extension",
        "extra": {
            "typo3/cms": {
                "extension-key": "example_extension",
                "version": "1.2.3-beta2",
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

State values that are not in the list of supported Composer stability values
can be expressed as build metadata:

..  code-block:: json
    :caption: composer.json using build metadata for custom state labels

    {
        "name": "vendor/example",
        "type": "typo3-cms-extension",
        "description": "Example extension",
        "extra": {
            "typo3/cms": {
                "extension-key": "example_extension",
                "version": "1.0.0+obsolete",
                "Package": {
                    "providesPackages": {}
                }
            }
        }
    }

In this example, `obsolete` is preserved as build metadata and can still be displayed
in the TYPO3 Extension Manager.

The former `state = excludeFromUpdates` value should now be expressed via
a dedicated boolean flag:

..  code-block:: json
    :caption: composer.json marking an extension as excluded from updates

    {
        "name": "vendor/example",
        "type": "typo3-cms-extension",
        "description": "Example extension",
        "extra": {
            "typo3/cms": {
                "extension-key": "example_extension",
                "version": "1.0.0",
                "exclude-from-updates": true,
                "Package": {
                    "providesPackages": {}
                }
            }
        }
    }

PHP constraints
---------------

If an extension declares a PHP version dependency, it should be in
the `require` section of :file:`composer.json`:

..  code-block:: json
    :caption: composer.json defining a PHP version constraint

    {
        "name": "vendor/example",
        "version": "1.0.0",
        "type": "typo3-cms-extension",
        "description": "Example extension",
        "require": {
            "typo3/cms-core": "^14.2",
            "php": "^8.2"
        },
        "extra": {
            "typo3/cms": {
                "extension-key": "example_extension",
                "Package": {
                    "providesPackages": {}
                }
            }
        }
    }

The PHP dependency remains relevant for metadata and compatibility checks
in TYPO3 classic mode, but it is not used for extension dependency ordering.

If an extension provides regular Composer packages itself in TYPO3 classic mode,
these packages must be declared in
`extra.typo3/cms.Package.providesPackages`.

Packages that are already shipped by TYPO3 or already provided by another loaded
extension do not need to be repeated there.

Entries in `providesPackages` may also associate a provided package with a
relative path to a Composer vendor directory inside the extension. If that
directory contains a Composer-generated `autoload.php`, TYPO3 includes it
early during bootstrap.

If an extension does not provide any regular Composer packages itself,
`providesPackages` must still be present and set to an empty object
to avoid deprecation messages and to declare future compatibility
with TYPO3 classic mode.

If strict :file:`composer.json` validation is required and the extension is published
to Packagist where setting the top level `version` field is not recommended,
it is recommended to set the version via `extra.typo3/cms.version`.

If the `version` field is set anyway, it is recommended to omit `extra.typo3/cms.version`
to avoid redundant data points.

Impact
======

There is no impact on Composer-based TYPO3 installations.

TYPO3 classic installations will trigger a deprecation message
for extensions that ship a :file:`ext_emconf.php` and have not defined
the required metadata in :file:`composer.json`.

Affected installations
======================

TYPO3 classic installations are affected if they use extensions that:

*   still ship :file:`ext_emconf.php`
*   do not define a `"version"` field or `extra.typo3/cms.version`
*   or do not define `extra.typo3/cms.Package.providesPackages`
    at all, even as an empty object

Migration
=========

Extension authors should move extension metadata from :file:`ext_emconf.php`
to :file:`composer.json`.

This includes:

* the extension version via `"version"` or `extra.typo3/cms.version`
* `providesPackages` via `extra.typo3/cms.Package.providesPackages`,
  using it for packages provided by the extension itself; packages already
  shipped by TYPO3 or already provided by another extension do not need
  to be repeated
* optional autoload paths for self-provided Composer packages via
  `extra.typo3/cms.Package.providesPackages`, pointing to a Composer
  vendor directory whose `autoload.php` can be included early
* supported stability via version suffixes such as `-dev`, `-alpha1`,
  `-beta2`, or `-RC3`
* custom former state labels via build metadata such as `+obsolete`
* update exclusion via `extra.typo3/cms.exclude-from-updates`
* PHP constraints via the :file:`require.php` entry

For the time being, :file:`ext_emconf.php` may still need to be kept for
third-party tooling such as TYPO3 TER or Tailor. However, once the
required metadata is correctly defined in :file:`composer.json`,
TYPO3 will no longer evaluate :file:`ext_emconf.php`.

.. index:: ext:core, NotScanned
