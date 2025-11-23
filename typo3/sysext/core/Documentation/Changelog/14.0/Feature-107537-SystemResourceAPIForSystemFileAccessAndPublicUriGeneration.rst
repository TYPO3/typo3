..  include:: /Includes.rst.txt

..  _feature-107537-1759136314:

=======================================================================================
Feature: #107537 - System resource API for system file access and public URI generation
=======================================================================================

See :issue:`107537`

Description
===========

TYPO3 allows files to be configured for multiple purposes. For example, a logo
to be shown on the login page, CSS and JavaScript files to be added to web
pages, or icons to be used for records. Most of the time, these are specified
using the `EXT` syntax to reference a file within an extension. However, it can
sometimes also be useful to reference an external URL or a file from a
:abbr:`FAL (File Abstraction Layer)` storage.

To achieve this, TYPO3 Core code previously needed to parse the specified
configuration, identify the type (extension file, FAL, URL), and then generate a
URL from it. While there was some API available for this purpose, it was
incomplete and consisted of multiple parts that were not clearly named, making
them easy to misuse. As a consequence, code in the TYPO3 Core differed depending
on where the configuration was used.

For users, this could lead to issues when a specific resource syntax worked in
one place (for example, in TypoScript) but not in another (for example, in a
Fluid email template). Moreover, the generated URLs could be inconsistent —
sometimes including a cache-busting addition and sometimes not.

Third-party developers struggled with a scattered API that was hard to use and
understand, especially regarding which methods should be called, in what order,
and how to maintain compatibility between Composer mode and classic mode.

The new **System Resource API** addresses these shortcomings and enables many
additional features to be built on top of it.

The code for resource resolving and URL generation is now encapsulated in a
precise and centralized API in one place. This not only makes it easier to
maintain and fix bugs, but ensures that such fixes apply automatically to all
parts of the system where resources are configured.

Before diving into the details of the API, some terminology is clarified,
followed by a top-level overview.

Naming conventions and top-level overview
-----------------------------------------

A **system resource** is a file or folder within a TYPO3 project. This can be a:

* **package resource** – a file within an extension
* **FAL resource** – a file from a :abbr:`FAL (File Abstraction Layer)` storage
* **app resource** – a file in the TYPO3 project folder
* **URI resource** – a URL

Package resource
^^^^^^^^^^^^^^^^

A package resource can now be specified with a new syntax like this:

`PKG:my-vendor/package-name:Resources/Public/Icons/Extension.svg`

It consists of three parts, separated by a colon (`:`):

#.  `PKG` prefix
#.  Composer name of the package (also possible in classic mode for extensions
    that contain a :file:`composer.json`)
#.  Relative path to the file within the package

The well-known `EXT` syntax can still be used for the time being:

`EXT:ext_name/Resources/Public/Icons/Extension.svg`

This syntax is not yet deprecated, but users are advised to switch to the new
syntax for new projects.

App resource
^^^^^^^^^^^^

An app resource is a file or folder within your TYPO3 installation. Such files
can also be specified using the `PKG` syntax, using the virtual name
`typo3/app` as package name:

`PKG:typo3/app:public/typo3temp/assets/style.css`

By default, only access to a fixed set of folders is allowed:
`public/_assets`, `public/typo3temp/assets`, and `public/uploads`.
Additional allowed folders can be configured via:

:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths']`

..  note::
    While `public` is the default public directory in Composer-based
    installations, it can be configured differently (for example `web`,
    `htdocs`, `html`, or `www`). In that case, the correct public directory
    must be specified for the resource, for example
    `PKG:typo3/app:web/typo3temp/assets/style.css`.

FAL resource
^^^^^^^^^^^^

While users are encouraged to use package and app resources, it is also
possible to specify files from :abbr:`FAL (File Abstraction Layer)` storages
using the following syntax:

..  code-block:: text
    FAL:1:/identifier/of/file.svg

It consists of three parts, separated by a colon (`:`):

#.  `FAL` prefix
#.  Storage ID (UID of the FAL storage record)
#.  Identifier of the file within this storage (for hierarchical storages,
    a slash-separated path)

URI resource
^^^^^^^^^^^^

A fully qualified URL (including HTTP(S) scheme) can be specified:

`https://www.example.com/my/image.svg`

URIs relative to the current host can be specified by prefixing them with
`URI:` like so:

`URI:/path/to/my/image.svg`

The string after the `URI:` prefix **must** be a valid URI. This means,
that TYPO3 will now throw an exception, rather than rendering an invalid URI
to HTML, when an invalid URI is provided.

Legacy resource annotations
^^^^^^^^^^^^^^^^^^^^^^^^^^^

The following legacy string representations can still be used, but they are
deprecated and will be removed in future TYPO3 versions:

*   FAL resource (relative path to the default FAL storage):
    `fileadmin/identifier/of/file.svg`
*   App resource (relative path to the project’s public directory):
    `typo3temp/assets/style.css`
*   App resource (relative path to the project’s public directory):
    `_assets/vite/foo.css`

All representations mentioned here are **resource identifiers**. They are
strings that uniquely identify a resource within the system.

API description
---------------

The PHP API consists of two parts:

#.  Resource resolving
#.  URI generation

The result of resource resolving is an object (different objects for different
resource types), which can then be passed to the API for URI generation.

Example PHP usage
-----------------

..  code-block:: php

    use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
    use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
    use TYPO3\CMS\Core\SystemResource\Publishing\UriGenerationOptions;
    use Psr\Http\Message\ServerRequestInterface;

    public function __construct(
        private readonly SystemResourceFactory $systemResourceFactory,
        private readonly SystemResourcePublisherInterface $resourcePublisher,
    ) {}

    public function renderUrl(string $resourceIdentifier, ServerRequestInterface $request): string
    {
        $resource = $this->systemResourceFactory->createPublicResource($resourceIdentifier);
        return (string)$this->resourcePublisher->generateUri(
            $resource,
            $request,
            new UriGenerationOptions(absoluteUri: true),
        );
    }

The :php-short:`\TYPO3\CMS\Core\SystemResource\SystemResourceFactory` and an
implementation of a system resource publisher are injected via dependency
injection (DI). The resource publisher is referenced using the interface
:php-short:`\TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface`.

..  note::
    In the future, different publishing strategies might be implemented, for
    example publishing resources directly to a CDN. In one TYPO3 installation,
    however, there can be only one default implementation, which can be
    configured to be used when the interface is referenced.

In the `renderUrl()` method, the `resourceFactory` is used to obtain a resource
object from a given resource identifier using `createPublicResource()`. This
object is then passed to the `generateUri()` method of the `resourcePublisher`.

The method has a second required argument for the current request. If the code
has no access to the current request, `null` can be passed instead. Passing
`null` is discouraged and might be deprecated in future TYPO3 versions. For CLI
commands, passing `null` is fine, but absolute URIs (including scheme and
hostname) cannot be generated in this case.

Example Fluid usage
-------------------

A new `<f:resource>` ViewHelper converts a resource identifier into an object
that can be passed to other ViewHelpers. Currently only `<f:uri.resource>`
accepts such resource objects, but more (for example `<f:image>`) will support
them in the future.

..  code-block:: html

    <html>
    <head>
        <link href="{f:resource(identifier:'PKG:typo3/cms-backend:Resources/Public/Css/webfonts.css') -> f:uri.resource()}" rel="stylesheet" />
    </head>
    <body>
        <f:asset.css identifier="main.css" href="PKG:my-vendor/site-package:Resources/Public/Css/main.css"/>
        <img src="{typo3.systemConfiguration.backend.loginLogo -> f:resource() -> f:uri.resource()}" alt="Logo" height="41" width="150" />
    </body>

All resource identifiers mentioned above can be passed to existing ViewHelpers
like `<f:asset>`.

Example TypoScript usage
------------------------

..  code-block:: typoscript

    page.includeCSS.extensionResource = EXT:backend/Resources/Public/Css/webfonts.css
    page.includeCSS.packageResource = PKG:my-vendor/ext-name:Resources/Public/Css/main.css
    page.includeCSS.appResource = PKG:typo3/app:public/_assets/style.css
    page.includeCSS.uriResource = https://www.example.com/css/main.css
    page.includeCSSLibs.fal = FAL:1:/templates/css/main.css

CSS and JavaScript files can be referenced using all resource identifiers
mentioned above.

Impact
======

All parts of the TYPO3 Core where URLs are generated from specified resources
now use the new API. This means all of those places support the new `PKG`
syntax and have cache busting applied automatically.

TYPO3 installations using other syntax than the supported ones need to migrate
their resource references. Resource identifiers such as
`typo3conf/ext/my_ext/Resources/Public/Image.svg` no longer work consistently
throughout the system and must be replaced with `EXT` or `PKG` resource
identifiers.

The benefits are:

*   Consistency throughout the system for system resource resolving and URL
    generation.
*   A more intuitive API that helps avoid mistakes, including security-related
    ones.
*   Consolidation of all code currently resolving system resources and
    generating URLs.
*   A foundation for future features such as publishing system resources to a
    CDN and letting TYPO3 generate CDN URLs directly instead of parsing HTML
    output.

Outlook
=======

This change is a major step forward, but further improvements are planned:

*   Implement flexible resource publishing in Composer and classic mode,
    allowing different publishing strategies and configuration of additional
    public resource folders or files.
*   Use this API in all areas that consume private resources, most notably
    Fluid template files.
*   Replace FAL storage for system assets completely by leveraging the `app`
    resource to contain templates, CSS files, logos, images, or even complete
    themes.

..  index:: ext:core
