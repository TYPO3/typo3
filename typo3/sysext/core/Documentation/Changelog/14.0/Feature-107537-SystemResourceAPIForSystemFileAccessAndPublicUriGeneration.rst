..  include:: /Includes.rst.txt

..  _feature-107537-1759136314:

=======================================================================================
Feature: #107537 - System Resource API for system file access and public URI generation
=======================================================================================

See :issue:`107537`

Description
===========

TYPO3 allows files to be configured for multiple purposes.
For example, a logo to be shown on the login page, CSS and JavaScript files to be added to web pages,
or icons to be used for records. Most of the time, these are specified using the `EXT` syntax
to reference a file within an extension. However, it can sometimes also be useful
to reference an external URL or a file from a FAL storage.

To achieve this, TYPO3 core code previously needed to parse the specified configuration,
identify the type (extension file, FAL, URL), and then generate a URL from it.
While there was some API available for this purpose, it was incomplete and consisted of multiple parts
that were not clearly named, making them easy to misuse. As a consequence,
code in the TYPO3 core differed depending on where the configuration was used.

For users, this could lead to issues when a specific resource syntax worked
in one place (e.g., in TypoScript) but not in another (e.g., in a Fluid email template).
Moreover, the generated URLs could be inconsistent — sometimes including a cache-busting addition and sometimes not.

Third-party developers struggled with a scattered API that was hard to use and understand,
especially regarding which methods should be called, in what order, and how to maintain
compatibility between "Composer mode" and "Classic mode".

The new **System Resource API** addresses these shortcomings and enables many additional
features to be built on top of it.

The code for resource resolving and URL generation is now encapsulated in
a precise and centralized API in one place.
This not only makes it easier to maintain and fix bugs, but ensures that such fixes
apply automatically to all parts of the system where resources are configured.

Before diving into the details of the API, some terminology is clarified,
followed by a top-level overview.

Naming conventions and top-level overview
-----------------------------------------

A **system resource** is a file or folder within a TYPO3 project.
This can be a **package resource** (a file within an extension),
a **file abstraction layer (FAL) resource**,
an **app resource** (a file in the TYPO3 project folder), or
a **URI resource** (a URL).

Package resource
^^^^^^^^^^^^^^^^

A package resource can now be specified with a new syntax like the so:
`PKG:my-vendor/package-name:Resources/Public/Icons/Extension.svg`
It consists of three parts, separated by a colon (`:`):

#. `PKG` prefix
#. composer name of the package
#. relative path to the file within the package

For the time being also the well known `EXT` syntax can be used:
`EXT:ext_name/Resources/Public/Icons/Extension.svg`

This syntax is not yet deprecated, but users are advised to switch
to the new syntax for new projects.

App resource
^^^^^^^^^^^^

An app resource a file or folder within your TYPO3 installation.
Such files can now also be specified using the `PKG` syntax, but
using the virtual name `typo3/app` as package name:
`PKG:typo3/app:public/typo3temp/assets/style.css`

By default only access to fixed set of folders is allowed,
these being: `public/_assets`, `public/typo3temp/assets`, `public/uploads`
Additional allowed folders can be configured via:
`$GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths']`

..  note::
    Be aware, that while `public` is the default public directory in Composer based installations,
    it can be configured to be a different directory (e.g. `web`). In that case, the correct public
    directory must be specified for the resource (e.g. `PKG:typo3/app:web/typo3temp/assets/style.css`)

FAL resource
^^^^^^^^^^^^

While users are encouraged to only use package and app resources instead,
it is additionally possible to specify files from FAL storages
using the following syntax:
`FAL:1:/identifier/of/file.svg`
It consists of three parts, separated by a colon (`:`):

#. `FAL` prefix
#. storage id (uid of FAL storage record)
#. identifier of the file within this storage (for hierarchical storages, slash separated path)

URI resource
^^^^^^^^^^^^

A fully qualified URL (including http(s) scheme), can be specified as well:
`https://www.example.com/my/image.svg`


Legacy resource annotations
^^^^^^^^^^^^^^^^^^^^^^^^^^^

For the time being, the following legacy string representations can be used as well,
but are deprecated and will not work any more in the future TYPO3 versions:

*   FAL resource (relative path to default FAL storage): `fileadmin/identifier/of/file.svg`

*   App resource (relative path to project's public dir): `typo3temp/assets/style.css`

*   App resource (relative path to project's public dir): `_assets/vite/foo.css`

It is highly recommended to switch the the FAL resource and App resource syntax
in new projects or during upgrade.

All representations for resources mentioned here are **resource identifiers**.
They are strings to uniquely identify a resource within the system.

API Description
---------------

The PHP API consists of two parts:

#. Resource resolving
#. URI generation

The result of resource resolving is an object (different objects for different resource types),
which then can be passed to the API for URI generation.

Let's examine the following PHP code example:

Example PHP usage
-----------------

..  code-block:: php

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

The `TYPO3\CMS\Core\SystemResource\SystemResourceFactory` and an implementation of a system resource publisher is injected via
dependency injection (DI) for use in your code. The resource publisher is referenced using
the interface `TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface`.

..  note::
    In the future different publishing strategies might be implemented, e.g. directly
    publishing resources to a CDN and the generating CDN URIs directly. In one TYPO3 installation
    there can however only be one default implementation, which can be configured to be used
    when the interface is referenced

Then in the `renderUrl` method, the `resourceFactory` is used to obtain a resource object
from a given resource identifier (see above for different possibilities) using `createPublicResource`.
This object is then passed to the `generateUri` method of the `resourcePublisher`.
The method has a second **required** argument for the **request**, which must be specified.
In case the current code does not have access to the current request, `null` can be specified
as well. Passing `null` is discouraged and might be deprecated in future TYPO3 versions.
Instead developers are advised to structure the API in third party code in a way, that the
request can be passed for URI generation. For CLI commands, however it is fine to pass null,
however absolute URIs (including scheme and hostname) can not be generated from the API.

Example Fluid usage
-------------------

There is a new `<f:resource>` ViewHelper, that has the only purpose to convert
a resource identifier into an object, that can be passed to other ViewHelpers.
Currently only the `<f:uri.resource>` accepts such resource objects, but more
will support those in the future such as `<f:image>`.

..  code-block:: html
    <html>
    <head>
        <link href="{f:resource(identifier:'PKG:typo3/cms-backend:Resources/Public/Css/webfonts.css') -> f:uri.resource()}" rel="stylesheet" />
    </head>
    <body>
        <f:asset.css identifier="main.css" href="PKG:my-vendor/site-package:Resources/Public/Css/main.css"/>
        <img src="{typo3.systemConfiguration.backend.loginLogo -> f:resource() -> f:uri.resource()}" alt="Logo" height="41" width="150" />
    </body>

All resource identifiers mentioned above can be passed to existing ViewHelpers like `<f:asset>`

Example TypoScript usage
------------------------

..  code-block:: typoscript

    page.includeCSS.extensionResource = EXT:backend/Resources/Public/Css/webfonts.css
    page.includeCSS.packageResource = PKG:my-vendor/ext-name:Resources/Public/Css/main.css
    page.includeCSS.appResource = PKG:typo3/app:public/_assets/style.css
    page.includeCSS.uriResource = https://www.example.com/css/main.css
    page.includeCSSLibs.fal = FAL:1:/templates/css/main.css

CSS and JavaScript files can be referenced using all resource identifiers mentioned above.

Impact
======

All places in TYPO3 core where URLs are generated from specified resources have been
adapted to use the new API. This means, all of those places now support the new `PKG`
syntax to specify resources and all resulting URLs have cache busting applied.

TYPO3 installations using other syntax than the mentioned supported ones need to migrate
resource reference to new and/or supported syntax. Most notably, resource identifiers like
`typo3conf/ext/my_ext/Resources/Public/Image.svg` do **not** work any more consistently
throughout the system and must be replaced using `EXT` or `PKG` resource identifier syntax.

The beneficial impacts are:

*   Consistency throughout the system regarding system resource
    resolving and URL generation.

*   A more intuitive API that helps to avoid (sometimes security related) mistakes.

*   Consolidation of all code that is currently resolving system resources and generating URLs.

*   Enabling features to be built on top, like publishing such system resources to a CDN
    and letting TYPO3 generate CDN URLs directly - instead of error-prone code that is
    trying to pick up URLs from parsed HTML output.

Outlook
=======

This change is a big first step, but it is not the last. In future efforts the following is planned:

* Implement flexible resource publishing in Composer and classic mode,
  allowing different publishing strategies, as well as configuring different and more
  public resource folders or files

* Using this API in all areas, that consume private resources, most notably Fluid template files

* Replace FAL storage for system assets completely, by leveraging the "app" to contain resources
  like Fluid templates, CSS files, logos, images, or even complete themes

..  index:: ext:core
