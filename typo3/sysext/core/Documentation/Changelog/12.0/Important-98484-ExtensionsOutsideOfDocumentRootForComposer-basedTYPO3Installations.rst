.. include:: /Includes.rst.txt

.. _important-98484-1664553704:

=========================================================================================================
Important: #98484 - Extensions and assets outside of document root for Composer-based TYPO3 installations
=========================================================================================================

See :issue:`98484`

Description
===========

TYPO3 v12 requires the Composer plugin `typo3/cms-composer-installers` with v5,
which automatically installs extensions into Composer's :file:`vendor/`
directory, just like any other regular dependency. This increases the default
security, so that files from extensions can no longer be accessed directly via
HTTP.

In order to allow serving assets (images/icons, CSS, JavaScript) from the
public web folder, every directory :file:`Resources/Public/` of any
installed extension is symlinked from their original location to
a directory called :file:`_assets/` within the public web folder (:file:`public/` by
default).

The name of a symlinked directory is created as a MD5 hash to prevent possible
information disclosure. As of now, this hash depends on the extension name
and its Composer project path, so it will not change upon deployment. The specific
hashing is an implementation detail that may be subject to change with future TYPO3
major versions.

For example, a file that was previously accessible as
:file:`public/typo3conf/ext/my_extension/Resources/Public/Images/logo.svg` will
now be stored in :file:`vendor/my-vendor/my-extension/Resources/Public/Images/logo.svg`
and be symlinked to :file:`public/_assets/9e592a1e5eec5752a1be78133e5e1a60/Resources/Public/Images/logo.svg`.

Impact
======

Please note that this only affects TYPO3 installations in Composer mode:

*   Any references from your Fluid templates, CSS/JavaScript files (or similar)
    that pointed to `typo3conf/ext/...` must now be changed (search your extension
    code for `typo3conf/ext/`). Ideally change code within Fluid or TypoScript, so
    that you can use a `EXT:my_extension/Resources/Public/...`
    reference. Those will automatically point to the right :file:`_assets` directory.
    For example, the :fluid:`f:uri.resource` ViewHelper will help you with this, as
    well as the TypoScript :ref:`stdWrap insertData and data path <t3tsref:data-type-gettext-path>` or
    :ref:`typolink <t3tsref:typolink>` / :ref:`IMG_RESOURCE <t3tsref:cobj-img-resource>`
    functionality. Also, in most YAML definitions you can use
    the `EXT:my_extension/Resources/Public/...` notation.
*   Adjust possible frontend build pipelines which previously wrote files into
    :file:`typo3conf/ext/...` so that they are now put into your extension source
    directory (for example, :file:`packages/my-extension/...`).
*   Any other static links to these files (like PHP API endpoints) must be changed
    to either utilize dynamic routes, middleware endpoints or static files/directories
    from custom directories in your project's public web path.
*   References within the same extension should use relative links, for example use
    :css:`background-image: url('../Images/logo.jpg')` instead of
    :css:`background-image: url('/typo3conf/ext/my_extension/Resources/Public/Images/logo.jpg')`.
*   You can use TypoScript/PHP/Fluid as mentioned above to create variables with
    resolved asset URI locations. These variables can utilize the
    `EXT:my_extension/Resources/Public/...` notation, and can be passed along
    to a JavaScript variable or a HTML DOM/data attribute, so it can be further evaluated.
*   If one extension links to an asset from another extension, and you cannot use
    the `EXT:my_extension/Resources/Public/...` syntax (for example, background images
    in a CSS file) you should either:

    *   Create a central, sitepackage-like extension that can take care of delivering
        all assets. CSS classes could be defined that refer to assets, and then other
        extensions could use the CSS class, instead of utilizing
        their own :css:`background-image: url(...)` directives. Ideally, use a bundler
        for your CSS/JavaScript (for example Vite, webpack, grunt/gulp, encore, ...)
        so that you only have a single extension that is responsible for shared assets.
        Bundlers can also help you to have a central asset storage, and distribute
        copies of these assets to all dependencies/sub-packages that depend on these assets.
    *   Utilize a PSR middleware or dynamic routes to "listen" on a specific URL like
        :file:`dynamicAssets/logo.jpg` and create a wrapper that returns specific files,
        resolved via the TYPO3 method
        :php:`PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName('EXT:my-extension/Resources/Public/logo.jpg')`.
    *   If all else fails: You can link to the full MD5 hashed URL, like
        :css:`background-image: url('/_assets/9e592a1e5eec5752a1be78133e5e1a60/Images/logo.jpg')`
        (or create a custom stable symlink, for example within your deployment, that points
        to the hashed directory name).
        The caveat of this: the hashing method may change in future TYPO3 major versions,
        and since the hash is based on a Composer project directory, this is only a suitable
        workaround for custom projects, and not publicly available extensions that need to
        work in all installations. Changes to the location/name of the :file:`vendor/` directory
        would then break frontend functionality.

For more details and the background about the change, read more:

* https://usetypo3.com/composer-changes-for-typo3-v11-and-v12.html
* https://b13.com/core-insights/typo3-and-composer-weve-come-a-long-way
* https://brotkrueml.dev/migration-typo3-composer-cms-installers-version-4/
* :ref:`Documentation on public/_assets/ structure <t3coreapi:directory-public-assets>`

.. index:: CLI, PHP-API, ext:core
