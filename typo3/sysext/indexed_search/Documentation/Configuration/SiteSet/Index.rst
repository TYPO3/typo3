:navigation-title: Site set

..  include:: /Includes.rst.txt

..  _site-set:

=========================
Site set "Indexed Search"
=========================

..  versionadded:: 13.3
    The system extension :composer:`typo3/cms-indexed-search` provides a site
    set with default settings.

Include the site set "Indexed Search" via the :ref:`site set in the site
configuration <t3coreapi:site-sets>` or the custom
:ref:`site package's site set <t3sitepackage:site_set>`.

..  figure:: /Images/SiteSet.png

    Add the site set "Indexed Search"

This will change your site configuration file as follows:

..  literalinclude:: _site_config.diff
    :caption: config/sites/my-site/config.yaml (diff)

If your site has a custom :ref:`site package <t3sitepackage:start>`, you
can also add the "Indexed Search" set as dependency in your site set's configuration:

..  literalinclude:: _site_package_set.diff
    :caption: EXT:my_site_package/Configuration/Sets/MySite/config.yaml (diff)

..  _site-set-settings:

Settings of the site set "Indexed Search"
=========================================

These settings can be adjusted in the :ref:`settings-editor`.

..  typo3:site-set-settings:: PROJECT:/Configuration/Sets/IndexedSearch/settings.definitions.yaml
    :name: indexed-search
    :type:
    :Label: Settings of "Indexed Search"
