.. include:: /Includes.rst.txt


.. _configuration:

=============
Configuration
=============

Target group: **Developers, Integrators**

..  seealso::
    General SEO recommendations for TypoScript and Site Configuration can be
    found in `TYPO3 explained, Suggested configuration options for improved
    SEO in TYPO3 <https://docs.typo3.org/permalink/t3coreapi:seo-configuration>`_.

..  toctree::
    :caption: Subpages
    :glob:

    *

.. _configuration-site-sets:

Site sets
=========

..  versionadded::13.3
    EXT:seo now offers a site set "SEO Sitemap"  to include the TypoScript to
    output the XML sitemap.

Include the site set "SEO Sitemap", `typo3/seo-sitemap` via the :ref:`site set in the site
configuration <t3coreapi:site-sets>` or the custom
:ref:`site package's site set <t3sitepackage:site_set>`.

Settings for the included set can be adjusted in the :ref:`settings-editor`.

..  figure:: /Images/SiteSet.png

    Add the site set "SEO Sitemap"

This will change your site configuration file as follows:

..  literalinclude:: _site_config.diff
    :caption: config/sites/my-site/config.yaml (diff)

If your site has a custom :ref:`site package <t3sitepackage:start>`, you
can also add the "SEO Sitemap" set as dependency in your site's configuration:

..  literalinclude:: _site_package_set.diff
    :caption: EXT:my_site_package/Configuration/Sets/MySite/config.yaml (diff)
