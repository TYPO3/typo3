.. include:: ../../Includes.txt

===========================================================================
Important: #86577 - Query parameters are now included in canonicalized URLs
===========================================================================

See :issue:`86577`

Description
===========

Canonicalized URLs include all query parameters which are needed to define what content to show
on a page. These URLs are used for the canonical URL and the hreflang URLs.
This is especially important with for example detail pages of records. The query parameters are 
crucial to show the right content.

Possibility to define query parameters to be included in canonicalized URLs
---------------------------------------------------------------------------

By default only parameters that are needed to calculate the cHash are included in the
canonicalized URLs. If you want to add your own parameters that should be included in those
URLs, you can use the newly introduced configuration option
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['additionalCanonicalizedUrlParameters']`. You can add
your own query parameters by adding them as elements of the array.

An example:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['FE']['additionalCanonicalizedUrlParameters'] => [
       'queryParam1',
       'queryParam2',
   ]

This example will add query parameters `queryParam1` and `queryParam2` to the canonicalized
URLs if they are provided.

.. important::

    Be careful when adding your own parameters. Only add those parameters which will change the
    content of your page. Otherwise search engines will most likely indicate your pages as
    duplicate content.

.. index:: ext:seo, ext:frontend, PHP-API
