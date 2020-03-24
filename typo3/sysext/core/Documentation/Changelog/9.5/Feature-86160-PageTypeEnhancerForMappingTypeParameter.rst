.. include:: ../../Includes.txt

==============================================================
Feature: #86160 - PageTypeEnhancer for mapping &type parameter
==============================================================

See :issue:`86160`

Description
===========

A new Route Enhancer is added to the newly introduced Routing functionality which allows to add
a suffix to the existing route (including existing other enhancers) to map a page type (GET parameter &type=)
to a suffix.

It is now possible to map various page types to endings.

Example TypoScript:

.. code-block:: typoscript

   page = PAGE
   page.typeNum = 0
   page.10 = TEXT
   page.10.value = Default page

   rssfeed = PAGE
   rssfeed.typeNum = 13
   rssfeed.10 < plugin.tx_myplugin
   rssfeed.config.disableAllHeaderCode = 1
   rssfeed.config.additionalHeaders.10.header = Content-Type: xml/rss

   jsonview = PAGE
   jsonview.typeNum = 26
   jsonview.config.disableAllHeaderCode = 1
   jsonview.config.additionalHeaders.10.header = Content-Type: application/json
   jsonview.10 = USER
   jsonview.10.userFunc = MyVendor\MyExtension\Controller\JsonPageController->renderAction

Now configure the Route Enhancer in your site's :file:`config.yaml` file like this:

.. code-block:: yaml

   routeEnhancers:
      PageTypeSuffix:
         type: PageType
         default: ''
         map:
            'rss.feed': 13
            '.json': 26


The :yaml:`map` allows to add a filename or a file ending and map this to a :ts:`page.typeNum` value.

It is also possible to set :yaml:`default` to e.g. ".html" to add a ".html" suffix to all default pages.

.. code-block:: yaml

   routeEnhancers:
      PageTypeSuffix:
         type: PageType
         default: '.json'
         index: 'index'
         map:
            'rss.feed': 13
            '.json': 26

The :yaml:`index` property is used when generating links on root-level page, thus, instead of e.g. having
`/en/.json` thus would then result in `/en/index.json`.

Impact
======

The TYPO3 Frontend-internal `&type` parameter can now also be part of a human readable URL with a simple
line of configuration.

Please note that the implementation is a Decorator Enhancer, which means that the PageTypeEnhancer
is only there for adding suffixes to an existing route / variant, but not to substitute something
within the middle of a human readable URL segment.

.. index:: Frontend
