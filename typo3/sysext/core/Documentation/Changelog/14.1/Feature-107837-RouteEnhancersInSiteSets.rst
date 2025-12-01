..  include:: /Includes.rst.txt

..  _feature-107837-1732800000:

===============================================
Feature: #107837 - Route enhancers in site sets
===============================================

See :issue:`107837`

Description
===========

Site sets can now define route enhancers in a dedicated :file:`route-enhancers.yaml`
file. This allows extensions to provide route enhancers as part of their site set
configuration, which are automatically merged into the site configuration when
the set is used as a dependency.

The route enhancers from site sets are applied as presets. This means that
site-level route enhancer configuration takes precedence and can override
set-defined enhancers.

Usage
=====

Create a :file:`route-enhancers.yaml` file in your site set directory alongside
the :file:`config.yaml`:

..  code-block:: none

    EXT:my_extension/Configuration/Sets/MySet/
    ├── config.yaml
    └── route-enhancers.yaml

The file must contain a `routeEnhancers` key with the route enhancer definitions:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Sets/MySet/route-enhancers.yaml

    routeEnhancers:
      MyEnhancer:
        type: Simple
        routePath: '/my-path/{param}'
        aspects:
          param:
            type: StaticValueMapper
            map:
              value1: '1'
              value2: '2'

The route enhancers file supports YAML imports, allowing you to split
configuration across multiple files:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Sets/MySet/route-enhancers.yaml

    imports:
      - { resource: 'route-enhancers/*.yaml' }

    routeEnhancers:
      # Additional enhancers can be defined here

Merging behavior
================

Route enhancers from site sets are merged in dependency order. When a site
uses multiple sets, enhancers from earlier dependencies are loaded first,
and later sets can override them.

Site-level route enhancer configuration always takes precedence over
set-defined enhancers. This allows sites to customize or override
preset configurations from sets.

Example
-------

Given a site set with:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Sets/MySet/route-enhancers.yaml

    routeEnhancers:
      PageType:
        type: PageType
        default: '0'
        map:
          feed.xml: '100'

And a site configuration with:

..  code-block:: yaml
    :caption: config/sites/my-site/config.yaml

    dependencies:
      - my_extension/my-set

    routeEnhancers:
      PageType:
        type: PageType
        default: '100'
        map:
          rss.xml: '200'

The resulting configuration will be:

..  code-block:: yaml

    routeEnhancers:
      PageType:
        type: PageType
        default: '100'
        map:
          feed.xml: '100'
          rss.xml: '200'

Scalar values from the site configuration override set-defined values,
while new keys are appended.

Impact
======

Extensions can now ship route enhancers as part of their site sets, providing
a streamlined way to configure routing for extension functionality. This is
particularly useful for extensions that require specific URL patterns, such
as sitemap extensions or API endpoints.

Invalid route enhancer configurations are handled gracefully: sets with
invalid :file:`route-enhancers.yaml` files are skipped and logged, similar
to other set validation errors.

..  index:: Frontend, YAML, ext:core
