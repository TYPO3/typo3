:navigation-title: Site set inclusion

..  include:: /Includes.rst.txt

..  _include-site-set:

======================================
Include one of the available site sets
======================================

..  versionadded:: 13.1
    Site sets have been introduced and are the recommended method to include
    TypoScript. If you do not want to use site sets, you can still use
    :ref:`TypoScript includes <include-default-typoscript>` to include the
    default TypoScript.

To use the default rendering definitions provided by *fluid_styled_content*, you
should add one of the two :ref:`site sets <site-sets>` provided by this extension
to your :ref:`site configuration <include-site-set-site>`
or depend on it in your :ref:`site package's site set <include-site-set-sitepackage>`.

..  _include-site-set-site:

Add the site set to your site configuration
===========================================

This is the recommended way to include the "Fluid Styled Content" site set into
basic TYPO3 sites without a custom site package. See also
:ref:`site sets <t3coreapi:site-sets>`.

.. figure:: /Images/ManualScreenshots/SiteSet.png

   Add the site set of Fluid Styled Content

When saving, the GUI then automatically updates your site configuration:

..  literalinclude:: _site_config.diff
    :caption: config/sites/my-site/config.yaml (diff)

..  _include-site-set-sitepackage:

Add the site set to your custom site package
============================================

If your installation has a custom :ref:`site package <t3sitepackage:start>`,
it is recommended to depend on the "Fluid Styled Content CSS" site set with your
site package's site set:

..  literalinclude:: _site_package_config.yaml
    :caption: EXT:my_site_package/Configuration/Sets/MySet/config.yaml

..  _include-default-site-set-next:

Next step
=========

:ref:`Display the content elements <inserting-content-page-template>` in your
site package template.
