:navigation-title: Template overrides

.. include:: /Includes.rst.txt

.. _configuration-fluidtemplating:

=============================
Overriding the Fluid template
=============================

..  versionchanged:: 13.3
    It is recommended to use the :ref:`site set settings <site-set-settings>` to
    override the template paths if possible.

The plugin "Indexed Search" can be extended with custom templates. You need
a custom :ref:`site package <t3sitepackage:start>` to achieve this.

The paths to the templates can also be extended in the :ref:`settings-editor`.

..  literalinclude:: _settings.yaml
    :caption: EXT:site_package/Configuration/Sets/SitePackage/settings.yaml

Now copy the Fluid templates that you want to override in the according paths
in your custom site package extension. For example to override the search form
copy the file :file:`EXT:indexed_search/Resources/Private/Partials/Form.html`
to :file:`EXT:site_package/Resources/Private/Extensions/IndexedSearch/Partials/Form.html`
and make your changes in the latter file.

.. _configuration-fluidtemplating-typoscript:

Overriding the template paths via TypoScript
============================================

If you need to override the Fluid templates from multiple locations or for
legacy reasons you do not use :ref:`site sets <site-set>` yet, you can use
TypoScript to override the template root paths:

The plugin "Indexed Search" can be extended with custom templates:

..  literalinclude:: _setup.typoscript
    :caption: EXT:my_extension/Configuration/TypoScript/setup.typoscript

The configuration in this TypoScript snippet will make the plugin look
templates in the following order:

*   Paths in `my_extension` (Index 20)
*   Paths defined by constant and if not defined by
    :ref:`settings <site-set-settings>` (Index 10)
*   Fall back to the default `indexed_search` templates (Index 0)
