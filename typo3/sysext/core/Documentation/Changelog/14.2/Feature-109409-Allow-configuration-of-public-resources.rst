..  include:: /Includes.rst.txt

..  _feature-109409-1774770383:

===================================================
Feature: #109409 - Allow configuration of resources
===================================================

See :issue:`109409`

Description
===========

Composer-managed TYPO3
----------------------

Until now, extensions could only place public resources in their
:folder:`Resources/Public` folder. The folder name used to publish these
extension resources was a non-configurable MD5 hash.

This feature introduces the possibility to configure resources explicitly.
This includes additional public folders or files that are published in
TYPO3's :folder:`public/_assets` folder, as well as non-public resource paths.

TYPO3 classic mode
------------------

In TYPO3 classic mode, there is no visible change for extensions
or the `typo3/app` package, since files in extensions are already
located within the document root. Restricting resources to the default
locations in classic mode therefore mainly follows coding guidelines
and keeps compatibility with Composer mode.

Configuring extensions
----------------------

Extensions can add :file:`Configuration/Resources.php` to configure resources.
This configuration is then added to the following default configuration:

..  code-block:: php
    :caption: EXT:core/Configuration/DefaultPackageResources.php

    <?php

    declare(strict_types=1);

    use TYPO3\CMS\Core\Package\Package;
    use TYPO3\CMS\Core\Package\Resource\Definition\PublicResourceDefinition;
    use TYPO3\CMS\Core\Package\Resource\Definition\ResourceDefinition;

    return static function (Package $package) {
        $resourceDefinitions = [
            new ResourceDefinition('Resources/Private'),
        ];
        if (is_dir($package->getPackagePath() . 'Resources/Public')) {
            $resourceDefinitions[] = new PublicResourceDefinition(
                'Resources/Public',
            );
        }
        return $resourceDefinitions;
    };

This means that when using the system resources API
(:ref:`feature-107537-1759136314`), resource identifiers are only allowed
to reference files or folders in :folder:`Resources/Private` and, if it exists,
:folder:`Resources/Public`. It also means that, by default,
:folder:`Resources/Public` in extensions is published in the same way as
before this change.

Example: Publish an additional public folder
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Resources.php

    <?php

    declare(strict_types=1);

    use TYPO3\CMS\Core\Package\Package;
    use TYPO3\CMS\Core\Package\Resource\Definition\PublicResourceDefinition;

    return static function (Package $package) {
        return [
            new PublicResourceDefinition('Build/Public'),
        ];
    };

This also publishes the :folder:`Build/Public` folder. The published folder
name is a hash unique to this folder.

Example: Publish a single file only
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Instead of publishing a whole folder, a single file can be published:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Resources.php

    <?php

    declare(strict_types=1);

    use TYPO3\CMS\Core\Package\Package;
    use TYPO3\CMS\Core\Package\Resource\Definition\PublicFileDefinition;

    return static function (Package $package) {
        return [
            new PublicFileDefinition(relativePath: 'Build/styles.css'),
            new PublicFileDefinition(
                relativePath: 'Build/components.css',
                publicPrefix: $package->getPackageKey() . '/custom/folder/my-components.css',
            ),
        ];
    };

This publishes the extension file :file:`Build/styles.css` to a folder with a
unique hash, which then contains the file :file:`styles.css`.
Additionally, :file:`Build/components.css` is published to
:file:`_assets/my_extension/custom/folder/my-components.css`.

Example: Use a fixed prefix in `_assets`
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, TYPO3 generates the public prefix automatically.
A fixed prefix can be configured explicitly:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Resources.php

    <?php

    declare(strict_types=1);

    use TYPO3\CMS\Core\Package\Package;
    use TYPO3\CMS\Core\Package\Resource\Definition\PublicResourceDefinition;
    use TYPO3\CMS\Core\Package\Resource\Definition\ResourceDefinition;

    return static function (Package $package) {
        return [
            new PublicResourceDefinition(
                relativePath: 'Build/Public',
                publicPrefix: 'my-vendor/my-extension-build',
            ),
        ];
    };

This publishes resources from :folder:`Build/Public` to a stable location below
:folder:`public/_assets/my-vendor/my-extension-build`.

..  note::

    Resource definitions configured in this file amend the default
    configuration. They are added to the default configuration.

..  important::

    Static public prefixes must be unique across all packages. Reusing the
    same public prefix in multiple packages raises an exception.

Configuring the typo3/app package
---------------------------------

See the system resources API (:ref:`feature-107537-1759136314`) for more
information about what the `typo3/app` package represents.

To configure the `typo3/app` package, a :file:`config/system/resources.php`
file can be added.

If it is missing, the following default configuration is used:

..  code-block:: php
    :caption: EXT:core/Configuration/DefaultAppResources.php

    <?php

    declare(strict_types=1);

    use TYPO3\CMS\Core\Package\Resource\Definition\PublicResourceDefinition;
    use TYPO3\CMS\Core\Package\VirtualAppPackage;

    return static function (VirtualAppPackage $package, string $relativePublicPath) {
        return [
            new PublicResourceDefinition(
                relativePath: $relativePublicPath . '_assets',
            ),
            new PublicResourceDefinition(
                relativePath: $relativePublicPath . 'uploads',
            ),
            new PublicResourceDefinition(
                relativePath: $relativePublicPath . 'typo3temp/assets',
            ),
        ];
    };

..  note::

    Resource definitions configured in this file amend the default
    configuration. They are added to the default configuration.

..  note::

    It is not recommended to place additional files in the
    :folder:`_assets` folder anymore. Instead, configure a folder **outside**
    the document root and let TYPO3 publish it automatically into
    :folder:`_assets`.

    This is important so that third-party publishers can pick up the
    publishing process and publish files to the intended location,
    for example a CDN, instead of leaving them in the :folder:`_assets` folder.
    If a resource definition configures a source folder that is already
    within the system's public folder, publishing is skipped.

Closing notes
-------------

For now, this change mainly affects public files and folders.

..  important::

    Only basic PHP operations are allowed in this file.
    TYPO3 is not bootstrapped in Composer mode when this file is evaluated.
    This means that, apart from classes being autoloadable, no global state
    is available. Think of this file as a plain PHP file that is executed
    directly as an entry point. Access to files **within** the package folder
    handed over as an object is allowed and intentional.

..  important::

    Relative paths must not contain leading or trailing slashes, backpaths
    such as `../`, or other invalid characters.

..  note::

    Changes to the resource configuration require execution of
    :bash:`composer dumpautoload` or :bash:`typo3 cache:flush` in TYPO3 classic
    mode.

Impact
======

If resource configuration is not added to an extension, this feature will have
no impact on a TYPO3 installation. If such a configuration exists,
it extends the default configuration.

..  index:: ext:core
