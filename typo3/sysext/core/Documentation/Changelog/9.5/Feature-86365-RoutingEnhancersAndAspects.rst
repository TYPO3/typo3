.. include:: ../../Includes.txt
.. highlight:: yaml

===============================================
Feature: #86365 - Routing Enhancers and Aspects
===============================================

See :issue:`86365`

Description
===========

Page-based routing is now flexible by adding enhancers to Routes that are generated or resolved with parameters, which
were previously appended as GET parameters.

An enhancer creates variants of a specific page-base route for a specific purpose (e.g. one plugin, one Extbase plugin)
and enhance the existing route path which can contain flexible values, so-called "placeholders".

On top, aspects can be registered to a specific enhancer to modify a specific placeholder, like static speaking names
within the route path, or dynamically generated.

To give you an overview of what the distinction is, we take a regular page which is available under

`https://www.example.com/path-to/my-page`

to access the Page with ID 13.

Enhancers are ways to extend this route with placeholders on top of this specific route to a page.

`https://www.example.com/path-to/my-page/products/{product-name}`

The suffix `/products/{product-name}` to the base route of the page is added by an enhancer. The placeholder variable
which is added by the curly braces can then be statically or dynamically resolved or built by an Aspect or more
commonly known a Mapper.

Enhancers and aspects are activated and configured in a site configuration, currently possible by modifying the
site's :file:`config.yaml` and adding the :yaml:`routeEnhancers` section manually, as there is no UI available for
this configuration. See examples below.

It is possible to use the same enhancers multiple times with different configurations, however, be aware that
it is not possible to combine multiple variants / enhancers that match multiple configurations.

However, custom enhancers can be built to overcome special use cases where e.g. two plugins with multiple parameters
each could be configured. Otherwise, the first variant that matches the URL parameters is used for generation and
resolving.

Enhancers
^^^^^^^^^

TYPO3 comes with the following enhancers out of the box:

- Simple Enhancer (enhancer type "Simple")
- Plugin Enhancer (enhancer type "Plugin")
- Extbase Plugin Enhancer (enhancer type "Extbase")

Custom enhancers can be registered by adding an entry to an extensions :file:`ext_localconf.php`.

:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['CustomPlugin'] = \MyVendor\MyPackage\Routing\CustomEnhancer::class;`

Within a configuration, an enhancer always evaluates the following properties:

* `type` - the short name of the enhancer as registered within :php:`$TYPO3_CONF_VARS`. This is mandatory.
* `limitToPages` - an array of page IDs where this enhancer should be called. This is optional. This property (array)
  evaluates to only trigger an enhancer for specific pages. In case of special plugin pages it is
  useful to only enhance pages with IDs, to speed up performance for building page routes of all other pages.

Simple Enhancer
---------------

The Simple Enhancer works with various route arguments to map them to a argument to be used later-on.

`index.php?id=13&category=241&tag=Benni`
results in
`https://www.example.com/path-to/my-page/241/Benni`

The configuration looks like this::

   routeEnhancers:
     # Unique name for the enhancers, used internally for referencing
     CategoryListing:
       type: Simple
       limitToPages: [13]
       routePath: '/show-by-category/{category_id}/{tag}'
       defaults:
         tag: ''
       requirements:
         category_id: '[0-9]{1..3}'
         tag: '^[a-zA-Z0-9].*$'
       _arguments:
         category_id: 'category'

The configuration option `routePath` defines the static keyword (previously known to some as "postVarSets" keyword for
some TYPO3 folks), and the available placeholders.

The `defaults` section defines which URL parameters are optional. If the parameters are omitted on generation, they
can receive a default value, and do not need a placeholder - it is also possible to add them at the very end of the
`routePath`.

The `requirements` section exactly specifies what kind of parameter should be added to that route as regular expression.
This way, it is configurable to only allow integer values for e.g. pagination. If the requirements are too loose, a
URL signature parameter ("cHash") is added to the end of the URL which cannot be removed.

The `_arguments` section defines what Route Parameters should be available to the system. In this example, the
placeholder is called `category_id` but the URL generation receives the argument `category`, so this is mapped to
this very name.

An enhancer is only there to replace a set of placeholders and fill in URL parameters or resolve them properly
later-on, but not to substitute the values with aliases, this can be achieved by Aspects.


Plugin Enhancer
---------------

The Plugin Enhancer works with plugins on a page that are commonly known as `Pi-Based Plugins`, where previously
the following GET/POST variables were used:

   `index.php?id=13&tx_felogin_pi1[forgot]=1&&tx_felogin_pi1[user]=82&tx_felogin_pi1[hash]=ABCDEFGHIJKLMNOPQRSTUVWXYZ012345`

The base for the plugin enhancer is to configure a so-called "namespace", in this case `tx_felogin_pi1` - the plugin's
namespace.

The Plugin Enhancer explicitly sets exactly one additional variant for a specific use-case. In case of Frontend Login,
we would need to set up multiple configurations of Plugin Enhancer for forgot and recover passwords.

::

   routeEnhancers:
     ForgotPassword:
       type: Plugin
       limitToPages: [13]
       routePath: '/forgot-password/{user}/{hash}'
       namespace: 'tx_felogin_pi1'
       defaults:
         forgot: "1"
       requirements:
         user: '[0-9]{1..3}'
         hash: '^[a-zA-Z0-9]{32}$'

If a URL is generated with the given parameters to link to a page, the result will look like this:

   `https://www.example.com/path-to/my-page/forgot-password/82/ABCDEFGHIJKLMNOPQRSTUVWXYZ012345`

If the input given to generate the URL does not meet the requirements, the route enhancer does not offer the
variant and the parameters are added to the URL as regular query parameters. If e.g. the user parameter would be more
than three characters, or non-numeric, this enhancer would not match anymore.

As you see, the Plugin Enhancer is used to specify placeholders and requirements, with a given namespace.

If you want to replace the user ID (in this example "82") with the username, you would need an aspect that can be
registered within any enhancer, but see below for details on Aspects.


Extbase Plugin Enhancer
-----------------------

When creating extbase plugins, it is very common to have multiple controller/action combinations. The Extbase Plugin
Enhancer is therefore an extension to the regular Plugin Enhancer, except for the functionality that multiple variants
are generated, typically built on the amount of controller/action pairs.

The `namespace` option is omitted, as this is built with `extension` and `plugin` name.

The Extbase Plugin enhancer with the configuration below would now apply to the following URLs:

* `index.php?id=13&tx_news_pi1[controller]=News&tx_news_pi1[action]=list`
* `index.php?id=13&tx_news_pi1[controller]=News&tx_news_pi1[action]=list&tx_news_pi1[page]=5`
* `index.php?id=13&tx_news_pi1[controller]=News&tx_news_pi1[action]=detail&tx_news_pi1[news]=13`
* `index.php?id=13&tx_news_pi1[controller]=News&tx_news_pi1[action]=archive&tx_news_pi1[year]=2018&&tx_news_pi1[month]=8`

And generate the following URLs

* `https://www.example.com/path-to/my-page/list/`
* `https://www.example.com/path-to/my-page/list/5`
* `https://www.example.com/path-to/my-page/detail/13`
* `https://www.example.com/path-to/my-page/archive/2018/8`

::

   routeEnhancers:
     NewsPlugin:
       type: Extbase
       limitToPages: [13]
       extension: News
       plugin: Pi1
       routes:
         - { routePath: '/list/{page}', _controller: 'News::list', _arguments: {'page': '@widget_0/currentPage'} }
         - { routePath: '/tag/{tag_name}', _controller: 'News::list', _arguments: {'tag_name': 'overwriteDemand/tags'}}
         - { routePath: '/blog/{news_title}', _controller: 'News::detail', _arguments: {'news_title': 'news'} }
         - { routePath: '/archive/{year}/{month}', _controller: 'News::archive' }
       defaultController: 'News::list'
       defaults:
         page: '0'
       requirements:
         page: '\d+'

In this example, you also see that the `_arguments` parameter can be used to bring them into sub properties of an array,
which is typically the case within demand objects for filtering functionality.

For the Extbase Plugin Enhancer, it is also possible to configure the namespace directly by skipping `extension`
and `plugin` properties and just using the `namespace` property as in the regular Plugin Enhancer.

Aspects
^^^^^^^

Now that we've looked into ways on how to extend a route to a page with arguments, and to put them into the URL
path as segments, the detailed logic within one placeholder is in an aspect. The most common practice of an aspect
is a so-called mapper. Map `{news_title}` which is a UID within TYPO3 to the actual news title, which is a field
within the database table.

An aspect can be a way to modify, beautify or map an argument from the URL generation into a placeholder. That's why
the terms "Mapper" and "Modifier" will pop up, depending on the different cases.

Aspects are registered within one single enhancer configuration with the option `aspects` and can be used with any
enhancer.

Let's start with some simpler examples first:


StaticValueMapper
-----------------

The StaticValueMapper replaces values simply on a 1:1 mapping list of an argument into a speaking segment, useful
for a checkout process to define the steps into "cart", "shipping", "billing", "overview" and "finish", or in a
simpler example to create speaking segments for all available months.

The configuration could look like this:

::

   routeEnhancers:
     NewsArchive:
       type: Extbase
       limitToPages: [13]
       extension: News
       plugin: Pi1
       routes:
         - { routePath: '/{year}/{month}', _controller: 'News::archive' }
       defaultController: 'News::list'
       defaults:
         month: ''
       aspects:
         month:
           type: StaticValueMapper
           map:
             january: 1
             february: 2
             march: 3
             april: 4
             may: 5
             june: 6
             july: 7
             august: 8
             september: 9
             october: 10
             november: 11
             december: 12


You'll see the placeholder "month" where the aspect replaces the value to a speaking segment.

It is possible to add an optional `localeMap` to that aspect to use the locale of a value to use in multi-language
setups.

::

    routeEnhancers:
      NewsArchive:
        type: Extbase
        limitToPages: [13]
        extension: News
        plugin: Pi1
        routes:
          - { routePath: '/{year}/{month}', _controller: 'News::archive' }
        defaultController: 'News::list'
        defaults:
          month: ''
        aspects:
          month:
            type: StaticValueMapper
            map:
              january: 1
              february: 2
              march: 3
              april: 4
              may: 5
              june: 6
              july: 7
              august: 8
              september: 9
              october: 10
              november: 11
              december: 12
          localeMap:
            - locale: 'de_.*'
              map:
                januar: 1
                februar: 2
                maerz: 3
                april: 4
                mai: 5
                juni: 6
                juli: 7
                august: 8
                september: 9
                oktober: 10
                november: 11
                dezember: 12


LocaleModifier
--------------

The enhanced part of a route path could be `/archive/{year}/{month}` - however, in multi-language setups, it should be
possible to rename `/archive/` depending on the language that is given for this page translation. This modifier is a
good example where a route path is modified but is not affected by arguments.

The configuration could look like this::

   routeEnhancers:
     NewsArchive:
       type: Extbase
       limitToPages: [13]
       extension: News
       plugin: Pi1
       routes:
         - { routePath: '/{localized_archive}/{year}/{month}', _controller: 'News::archive' }
       defaultController: 'News::list'
       aspects:
         localized_archive:
           type: LocaleModifier
           default: 'archive'
           localeMap:
             - locale: 'fr_FR.*|fr_CA.*'
               value: 'archives'
             - locale: 'de_DE.*'
               value: 'archiv'

You'll see the placeholder "localized_archive" where the aspect replaces the localized archive based on the locale of
the language of that page.


StaticRangeMapper
-----------------

A static range mapper allows to avoid the `cHash` and narrow down the available possibilities for a placeholder,
and to explicitly define a range for a value, which is recommended for all kinds of pagination functionalities.

::

   routeEnhancers:
     NewsPlugin:
       type: Extbase
       limitToPages: [13]
       extension: News
       plugin: Pi1
       routes:
         - { routePath: '/list/{page}', _controller: 'News::list', _arguments: {'page': '@widget_0/currentPage'} }
       defaultController: 'News::list'
       defaults:
         page: '0'
       requirements:
         page: '\d+'
       aspects:
         page:
           type: StaticRangeMapper
           start: '1'
           end: '100'

This limits down the pagination to max. 100 pages, if a user calls the news list with page 101, then the route enhancer
does not match and would not apply the placeholder.

PersistedAliasMapper
--------------------

If an extension ships with a slug field, or a different field used for the speaking URL path, this database field
can be used to build the URL::

    routeEnhancers:
      NewsPlugin:
        type: Extbase
        limitToPages: [13]
        extension: News
        plugin: Pi1
        routes:
          - { routePath: '/detail/{news_title}', _controller: 'News::detail', _arguments: {'news_title': 'news'} }
        defaultController: 'News::detail'
        aspects:
          news_title:
            type: PersistedAliasMapper
            tableName: 'tx_news_domain_model_news'
            routeFieldName: 'path_segment'
            routeValuePrefix: '/'

The PersistedAliasMapper looks up (via a so-called delegate pattern under the hood) to map the given value to a
a URL. The property `tableName` points to the database table, property `routeFieldName` is the field which will be
used within the route path for example.

The special `routeValuePrefix` is used for TCA type `slug` fields where the prefix `/` is within all fields of the
field names, which should be removed in the case above.

If a field is used for `routeFieldName` that is not prepared to be put into the route path, e.g. the news title field,
it still must be ensured that this is unique. On top, if there are special characters like spaces they will be
URL-encoded, to ensure a definitive value, a slug TCA field is recommended.

PersistedPatternMapper
----------------------

When a placeholder should be fetched from multiple fields of the database, the PersistedPatternMapper is for you.
It allows to combine various fields into one variable, ensuring a unique value by e.g. adding the UID to the field
without having the need of adding a custom slug field to the system.

::

   routeEnhancers:
     Blog:
       type: Extbase
       limitToPages: [13]
       extension: BlogExample
       plugin: Pi1
       routes:
         - { routePath: '/blog/{blogpost}', _controller: 'Blog::detail', _arguments: {'blogpost': 'post'} }
       defaultController: 'Blog::detail'
       aspects:
         blogpost:
           type: PersistedPatternMapper
           tableName: 'tx_blogexample_domain_model_post'
           routeFieldPattern: '^(?P<title>.+)-(?P<uid>\d+)$'
           routeFieldResult: '{title}-{uid}'

The `routeFieldPattern` option builds the title and uid fields from the database, the `routeFieldResult` shows
how the placeholder will be output.

Impact
======

Some notes to the implementation:

While accessing a page in TYPO3 in the Frontend, all arguments are currently built back into the global
GET parameters, but are also available as so-called `PageArguments` object, which is then used to be signed and verified
that they are valid, when handing them to process a frontend request further.

If there are dynamic parameters (= parameters which are not strictly limited), a verification GET parameter `cHash`
is added, which can and should not be removed from the URL. The concept of manually activating or deactivating
the generation of a `cHash` is not optional anymore, but strictly built-in to ensure proper URL handling. If you
really have the requirement to never have a cHash argument, ensure that all placeholders are having strict definitions
on what could be the result of the page segment (e.g. pagination), and feel free to build custom mappers.

Setting the TypoScript option `typolink.useCacheHash` is not necessary anymore when running with a site configuration.

Please note that Enhancers and Page-based routing is only available for pages that are built with a site configuration.

All existing APIs like `typolink` or functionality evaluate the new Page Routing API directly and come with route
enhancers.

Please note that if you update the Site configuration with enhancers that you need to clear all caches.

.. index:: Frontend, PHP-API
