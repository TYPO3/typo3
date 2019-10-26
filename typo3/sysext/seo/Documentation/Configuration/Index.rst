.. include:: ../Includes.txt


.. _configuration:

=============
Configuration
=============

Target group: **Developers, Integrators**


TypoScript Settings
===================

There are a couple of TypoScript settings that can influence the output regarding search engine optimization.

* `config.pageTitleFirst <https://docs.typo3.org/m/typo3/reference-typoscript/master/en-us/Setup/Config/Index.html#pagetitlefirst>`__
* `config.pageTitleSeparator <https://docs.typo3.org/m/typo3/reference-typoscript/master/en-us/Setup/Config/Index.html#pagetitleseparator>`__
* `config.pageTitleProviders <https://docs.typo3.org/m/typo3/reference-typoscript/master/en-us/Setup/Config/Index.html#pagetitleproviders>`__
* `config.noPageTitle <https://docs.typo3.org/m/typo3/reference-typoscript/master/en-us/Setup/Config/Index.html#nopagetitle>`__

Site configuration
==================

From version 9 of TYPO3, the configuration of sites is done with the Site Management module. As the settings for
your websites are important for SEO purposes as well, please make sure you check the following fields.

.. figure:: ../Images/site.png
   :class: with-shadow
   :alt: example site

   Example site

To get more in depth information about the site handling please refer to the :ref:`t3coreapi:sitehandling` docs.

Domains
-------

Please ensure, that you have configured your sites so that they all have an entry point. This is used for
generating the canonical tags, for example.

.. warning::

   Please be aware that for SEO purposes it is best practise to use a fully qualified domain (for example: https://www.example.com).
   Therefor we don't support the SEO enhancements in TYPO3 without a full domain. It might work, but it is not officially
   supported.

Language
--------

Ensure, that you setup the languages correctly. All languages should have the right information in the :guilabel:`Locale`
and :guilabel:`Language Tag` field. When set correctly, TYPO3 will automatically connect your page in the different languages
for search engines. This it to ensure that the search engine knows which page to show when someone is searching in a
specific language.

.. hint::

   Even if you have only one language, make sure your :guilabel:`Locale` and :guilabel:`Language Tag` fields are set correctly.
   Giving wrong information to search engines will not help you to rank higher.

See :ref:`t3coreapi:sitehandling-addingLanguages` for more details.

Error pages
-----------

Although TYPO3 will respond with a HTTP status code 404 (Not found) when a page is not found, it is best practise to
have a proper message telling the user that the page they requested is not available and to guide them to another
page or for example to a search function of your website.

See :ref:`t3coreapi:sitehandling-errorHandling` for more details.

robots.txt
----------

The robots.txt is a powerful tool and should be used with care. It will deny or allow search engines to access your pages.
By blocking access to your pages, search engines won't crawl these pages. You should make sure that this will not
prevent the search engines from finding important pages.

It is best practise to keep your robots.txt as clean as possible. An example of a minimal version of your robots.txt:

.. code-block:: php

   # This space intentionally left blank. Only add entries when you know how powerful the robots.txt is.
   User-agent: *

On :ref:`t3coreapi:sitehandling-staticRoutes` you can find more details on how to create a static route that will show
this information when visiting https://www.example.com/robots.txt.

When you want to disallow specific URLs, you can use the :ref:`index-page` option in the backend or set the robot HTTP
header `X-Robots-tag` manually.

Redirects
---------

Having correct redirects is a very important part of SEO. Especially the status code that is used for redirects is
really important. Please use the appropriate status code for your use case.

External Resources:

* See `Tutorial of redirect module in TYPO3 <https://www.toujou.de/en/service/tutorials/redirects/>`__ by toujou
* An `Overview which redirect to use in your situation <https://yoast.com/which-redirect/>`__ by Yoast

.. _config-tags:

Tags
====

.. _config-hreflang-tags:

Hreflang Tags
-------------

The generation of the :html:`<link rel="alternate" hreflang="" href="" />`
tags is done automatically if the page is available in other languages.
This feature should work correctly in almost all cases.
If you have a specific edge case, and you don't want TYPO3 to render those tags, you can disable rendering of the those tags completely.
You just have to put this line in the :file:`ext_localconf.php` of an extension and make sure your extension is loaded after EXT:seo.

.. code-block:: php

   unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Frontend\Page\PageGenerator']['generateMetaTags']['hreflang']);

.. _config-canonical-tag:

Canonical Tag
-------------

Just like the hreflang tags, the :html:`<link rel="canonical" href="" />` tag is also generated automatically.
If you have a specific edge case, and you don't want TYPO3 to render the tag, you can disable rendering completely.
You just have to put this line in the :file:`ext_localconf.php` of an extension and make sure your extension is loaded after EXT:seo.

.. code-block:: php

   unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Frontend\Page\PageGenerator']['generateMetaTags']['canonical']);

Working links
=============

Links in your website are quite important. You can use third party applications to check all your links, but you can
also use the core extension linkvalidator to ensure, all the links in your site are working as expected.

Please check the documentation of :ref:`linkvalidator:start` .

TypoScript examples
===================

This section will provide you examples on how to configure several behaviours in the frontend.

Setting fallbacks for og:image and twitter:image
------------------------------------------------

If you want to have a fallback og:image or twitter:image, you can use this little snippet.

.. code-block:: typoscript

   page {
     meta {
       og:image.stdWrap.cObject = IMG_RESOURCE
       og:image.stdWrap.cObject {
         file = EXT:your_extension/Resources/Public/Backend/OgImage.svg
       }
       twitter:image.stdWrap.cObject = IMG_RESOURCE
       twitter:image.stdWrap.cObject {
         file = EXT:your_extension/Resources/Public/Backend/TwitterCardImage.svg
       }
     }
   }

More information about the Meta Tag Api can be found on:

* PHP :ref:`t3coreapi:metatagapi`
* TypoScript :ref:`t3tsref:meta`

Setting defaults for the author on meta tags
--------------------------------------------

This example shows how to set a default author based on the TypoScript constant :ts:`{$my.default.author}`:

.. code-block:: typoscript

   page {
     meta {
       author = {$my.default.author}
     }
   }

.. seealso::

   :ref:`recommendations_field_description`
