..  include:: /Includes.rst.txt

..  _feature-109263-1773679470:

==========================================================================
Feature: #109263 - Expression language support for limitToPages in routing
==========================================================================

See :issue:`109263`

Description
===========

The :yaml:`limitToPages` option for route enhancers in the site configuration
now supports Symfony Expression Language expressions in addition to plain
page IDs.

Previously, :yaml:`limitToPages` accepted only an array of integer page IDs,
which required maintaining a static list that had to be updated whenever pages
were added or moved. This change means string entries in the
:yaml:`limitToPages` array are evaluated as Expression Language expressions,
giving integrators flexible, condition-based control over which pages a route
enhancer applies to.

All entries in the array are combined via logical OR. Integer values are
matched against the page ID (existing behavior), and string values are
evaluated as expressions. To combine multiple conditions with logical AND, use
the :yaml:`&&` operator inside a single expression string.

Use the following variables inside expressions:

*   :yaml:`page` - The full page record as an associative array (for example,
    :yaml:`page["doktype"]`, :yaml:`page["backend_layout"]`,
    :yaml:`page["module"]`)
*   :yaml:`site` - The current :php:`Site` object
*   :yaml:`siteLanguage` - The current :php:`SiteLanguage` object

All the default Expression Language functions such as
:yaml:`like()`, :yaml:`env()`, and :yaml:`feature()` are also available.
Extensions can register additional functions and variables for the
:yaml:`routing` Expression Language context via
:file:`Configuration/ExpressionLanguage.php`.

Examples
--------

Match pages by their page type (`doktype`):

..  code-block:: yaml
    :caption: config/sites/<identifier>/config.yaml

    routeEnhancers:
      NewsPlugin:
        type: Extbase
        limitToPages:
          - 'page["doktype"] == 1'
        extension: News
        plugin: Pi1
        routes:
          - routePath: '/list/{page}'
            _controller: 'News::list'
          - routePath: '/detail/{news_title}'
            _controller: 'News::detail'

Match pages by their backend layout:

..  code-block:: yaml
    :caption: config/sites/<identifier>/config.yaml

    routeEnhancers:
      BlogPlugin:
        type: Extbase
        limitToPages:
          - 'page["backend_layout"] == "pagets__blog"'
        extension: Blog
        plugin: Posts
        routes:
          - routePath: '/post/{post_title}'
            _controller: 'Post::show'

Combine integer page IDs with expression conditions (OR logic):

..  code-block:: yaml
    :caption: config/sites/<identifier>/config.yaml

    routeEnhancers:
      ShopPlugin:
        type: Extbase
        limitToPages:
          - 42
          - 'page["module"] == "shop"'
        extension: Shop
        plugin: Products
        routes:
          - routePath: '/product/{product_title}'
            _controller: 'Product::show'

Use AND logic inside a single expression:

..  code-block:: yaml
    :caption: config/sites/<identifier>/config.yaml

    routeEnhancers:
      SpecialPlugin:
        type: Extbase
        limitToPages:
          - 'page["doktype"] == 1 && page["backend_layout"] == "pagets__special"'
        extension: MyExtension
        plugin: Special
        routes:
          - routePath: '/item/{item_title}'
            _controller: 'Item::show'

Impact
======

Integrators can now use dynamic, expression-based conditions in
:yaml:`limitToPages` instead of maintaining static lists of page IDs. This is
fully backward compatible - existing configurations with integer-only arrays
continue to work without any changes.

..  index:: YAML, PHP-API, ext:core
