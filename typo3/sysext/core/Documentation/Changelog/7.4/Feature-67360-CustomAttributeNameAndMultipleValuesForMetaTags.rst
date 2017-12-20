
.. include:: ../../Includes.txt

=========================================================================
Feature: #67360 - Custom attribute name and multiple values for meta tags
=========================================================================

See :issue:`67360`

Description
===========

`page.meta` is extented to support different attribute names like `property` used for OG tags. You may also supply
multiple values for one name, which results in multiple meta tags with the same name to be rendered.

See http://ogp.me/ for more information about the Open Graph protocol and its properties.

.. code-block:: typoscript

    page {
        meta {
            X-UA-Compatible = IE=edge,chrome=1
            X-UA-Compatible.attribute = http-equiv

            keywords = TYPO3

            og:site_name = TYPO3
            og:site_name.attribute = property

            description = Inspiring people to share Normal

            dc\.description = Inspiring people to share [DC tags]

            og:description = Inspiring people to share [OpenGraph]
            og:description.attribute = property

            og:locale = en_GB
            og:locale.attribute = property

            og:locale:alternate {
                attribute = property
                value {
                    1 = fr_FR
                    2 = de_DE
                }
            }

            refresh = 5; url=http://example.com/
            refresh.attribute = http-equiv

        }
    }


Impact
======

Meta tags with a different attribute name are supported now like the Open Graph meta tags.


.. index:: TypoScript, Frontend
