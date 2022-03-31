.. include:: /Includes.rst.txt

.. _adjust-template-of-widget:

==========================
Adjust template of widgets
==========================

When adding own widgets, it might be necessary to provide custom templates.
In such a case the file path containing the template files needs to be added.

This is done using a :file:`Configuration/page.tsconfig` file, see
:doc:`changelog <ext_core:Changelog/12.0/Feature-96812-OverrideBackendTemplatesWithTSconfig>` and
:doc:`changelog <ext_core:Changelog/12.0/Feature-96614-AutomaticInclusionOfPageTsConfigOfExtensions>`
for details on this:

.. code-block:: typoscript

    # Pattern: templates.typo3/cms-dashboard."something-unique" = "overriding-extension-composer-name":"entry-path"
    templates.typo3/cms-dashboard.1644485473 = myvendor/myext:Resources/Private

A template file can then be added to path :file:`Resources/Private/Templates/Widgets/MyExtensionsGreatWidget.html`
and is referenced in the PHP class using :php:`->render('Widgets/MyExtensionsGreatWidget');`. The registration
into namespace :php:`typo3/cms-dashboard` is shared between all extensions. It is thus a good idea to give
template file names unique names (for instance by prefixing them with the extension name), to avoid situations
where templates from multiple extensions that provide different widgets override each other.
