.. include:: /Includes.rst.txt

.. _feature-104794-1725980585:

=================================================
Feature: #104794 - Introduce Site Settings Editor
=================================================

See :issue:`104794`

Description
===========

A new Site Settings editor has been introduced that allows to configure per-site
settings in file:`config/sites/*/settings.yaml`.

The new backend module :guilabel:`Site Management > Settings`
provides an overview of sites which offer configurable settings and makes
them editable based on
:doc:`Site Set provided Settings Definitions <../13.1/Feature-103437-IntroduceSiteSets>`.

The editor shows a list of settings categories and respective settings. It will
persist all settings into file:`config/sites/*/settings.yaml`. The module will
only persist settings that deviate from the site-scoped default value. That
means it will only change the minimal difference to the settings set defined
by the active sets for the respective site.

The backend module is currently available for administrators only, but will
likely be extended to be made available for editors in future.

Anonymous (undefined) site settings – as supported since TYPO3 v10 –
will not be made editable, but will be preserved as-is when persisting changes
through the settings editor.


Categorization
--------------

Sets can define categories and settings definitions can reference the category
by ID in order to assign a setting to a specific category.
These definitions are placed in :file:`settings.definitions.yaml`
next to the site set file :file:`config.yaml`.

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Sets/MySet/settings.definitions.yaml

    categories:
      myCategory:
        label: 'My Category'

    settings:
      my.example.setting:
        label: 'My example setting'
        category: myCategory
        type: string
        default: ''

      my.seoRelevantSetting:
        label: 'My SEO relevant setting'
        # show in EXT:seo provided category "seo"
        category: seo
        type: int
        default: 5

The settings ordering is defined through the loading order of extensions and by
the order of categories. Uncategorized settings will be grouped into a virtual
"Other" category and shown at the end of the list of available settings.


Impact
======

Site-scoped settings will most likely be the place to configure site-wide
configuration, which was previously only possible to modify via Constant Editor,
modifying TypoScript constants.

It is recommended to use site-sets and their UI configuration in favor of
Typoscript Constants in the future.


.. index:: Backend, Frontend, YAML, ext:backend
