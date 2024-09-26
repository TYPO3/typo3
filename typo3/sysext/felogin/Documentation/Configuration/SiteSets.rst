:navigation-title: Site Sets

..  include:: /Includes.rst.txt
.. _configuration-site-sets-include:

============================================
Site set configuration of the Frontend Login
============================================

..  versionadded:: 13.1
    Site sets were added.

The system extension :composer:`typo3/cms-felogin` provides the site
set "Frontend Login".

The different methods of setting are taking precedence in the following order:

..  include:: _SettingsOrder.rst.txt

..  contents::
    :caption: Content on this page
    :depth: 1

.. _configuration-site-set:

Include the site set
====================

Include the site set "Frontend Login" via the :ref:`site set in the site
configuration <t3coreapi:site-sets>` or the custom
:ref:`site package's site set <t3sitepackage:site_set>`.

.. figure:: /Images/SiteSet.png

   Add the site set "Frontend Login"

This will change your site configuration file as follows:

..  literalinclude:: _site_config.diff
    :caption: config/sites/my-site/config.yaml (diff)

If your site has a custom :ref:`site package <t3sitepackage:start>`, you
can also add the "Frontend Login" set as dependency in your site set's configuration:

..  literalinclude:: _site_package_set.diff
    :caption: EXT:my_site_package/Configuration/Sets/MySite/config.yaml (diff)

.. _configuration-site-set-settings:

Settings for the "Frontend Login" site set
==========================================

..  versionadded:: 13.1
    These settings were added with the site sets in TYPO3 v13.1.

See also: :ref:`configuration-examples-felogin-pid`.

If you plan to migrate from TypoScript setup settings to site settings see
:ref:`configuration-migration`.

These settings can be adjusted in the :ref:`settings-editor`.

..  typo3:site-set-settings:: PROJECT:/Configuration/Sets/Felogin/settings.definitions.yaml
    :name: felogin
    :type:
    :Label: max=36
    :caption: Settings of "Frontend Login"


..  _configuration-migration:

Migration from TypoScript setup settings to site settings
=========================================================

The site settings are named like the TypoScript constants used before
site sets. However the TypoScript constants are not always named the same
like the :ref:`TypoScript setup settings <plugin-tx-felogin-login>`.

For each :ref:`TypoScript setup / FlexForm setting <typo3/cms-felogin:plugin-tx-felogin-login>`
we list the corresponding site set setting in the overview table of the configuration values.

For example, the setting :confval:`felogin.pid <felogin-felogin-pid>` sets
setting :ref:`pages <pages>`.

Bear that in mind when migrating from TypoScript setup to site set settings.

..  _configuration-examples-felogin-pid:

Example: Set the user storage page using the site set settings
==============================================================

After you :ref:`included the site set <configuration-site-set>` you can use
the :ref:`site set settings <configuration-site-set-settings>` to configure
the frontend login plugin's behaviour and layout site-wide.

See also :ref:`Adding site settings <t3coreapi:sitehandling-settings-add>`.

You can add the settings to your :ref:`Site settings <t3coreapi:sitehandling-settings>`
or to the settings of your
:ref:`custom site package extension <t3sitepackage:start>`.

To add the settings to your site settings, edit the file
:file:`config/sites/<my_site>/settings.yaml` in Composer-based installations
or :file:`typo3conf/sites/<my_site>/settings.yaml` in legacy installations. If
the file does not exist yet, create one. Use the setting
:confval:`felogin.pid <felogin-felogin-pid>` to set the storage folder. If
its subfolders should also be included, additionally use setting
:confval:`felogin.recursive <felogin-felogin-recursive>`.

..  literalinclude:: _settings.yaml
    :caption: config/sites/<my_site>/settings.yaml | typo3conf/sites/<my_site>/settings.yaml
