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
the extension version and the `providesPackages` definition
in `composer.json`:

..  code-block:: json
    :caption: composer.json for an extension providing Composer packages

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

..  code-block:: json
    :caption: composer.json for an extension not providing Composer packages

    {
        "name": "vendor/example2",
        "type": "typo3-cms-extension",
        "description": "example",
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
or in the top level `version` field of `composer.json`.
This version must match the version previously
defined in `ext_emconf.php` and the released Git tag.

Fixture extensions used in tests can set any version number, for example `1.0.0`,
but a version number must still be provided to avoid deprecation messages.

During testing, the version number is not evaluated.

TYPO3 Core extensions may omit the version number
in `composer.json`, because their version can and will be derived from
php`\TYPO3\CMS\Core\Information\Typo3Version`.

If an extension depends on regular Composer packages, these packages
must be declared in
`extra.typo3/cms.Package.providesPackages`.

If an extension does not depend on any regular Composer packages,
`providesPackages` must still be present and set to an empty object
to avoid deprecation messages and to declare future compatibility
with TYPO3 classic mode.

If strict `composer.json` validation is required and the extension is published
to Packagist as well, where setting the top level `version` field is not recommended,
it is recommended to set the version via `extra.typo3/cms.version`.

If the `version` field is set anyway, it is recommended to omit `extra.typo3/cms.version`
to avoid redundant data points.

Impact
======

There is no impact on Composer-based TYPO3 installations.

TYPO3 classic installations will trigger a deprecation message
for extensions that still ship `ext_emconf.php` but do not yet define
both `extra.typo3/cms.version` (or `"version"` field ) *and* `providesPackages`
in `composer.json`.

Affected installations
======================

TYPO3 classic installations are affected if they use extensions that:

* still ship `ext_emconf.php`
* do not define the `"version"` field in `composer.json`
* or do not define `extra.typo3/cms.Package.providesPackages` in `composer.json`

Migration
=========

Extension authors should add the `"version"` field and the
`providesPackages` definition to `composer.json`
if their extensions should remain compatible with TYPO3 classic mode.

For the time being, `ext_emconf.php` may still need to be kept for
third-party tooling such as TYPO3 TER or Tailor. However, once the
required metadata is correctly defined in `composer.json`,
TYPO3 will no longer evaluate `ext_emconf.php`.

.. index:: ext:core, NotScanned
