.. include:: /Includes.rst.txt


.. _config-best-practices:

============================
Configuration Best Practices
============================

.. _best-practice-sitepackage:

Use a Sitepackage extension
===========================

It is generally recommended to use a sitepackage extension to
customize a TYPO3 website. The sitepackage contains configuration files
for that site.

See the :doc:`TYPO3 Sitepackage Tutorial <t3sitepackage:Index>` on how
to create a sitepackage. We assume here your sitepackage extension has the
key `my_sitepackage`.

The YAML preset files should be kept in folder
:file:`EXT:my_sitepackage/Configuration/RTE/`.

RTE configurations need to be registered in your sitepackages
:file:`ext_localconf.php`:

.. code-block:: php
   :caption: EXT:my_sitepackage/ext_localconf.php

   $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['myconfig']
      = 'EXT:my_sitepackage/Configuration/RTE/MyConfiguration.yaml';

.. note::
   It is possible but not recommended to define this setting in the projects
   :file:`LocalConfiguration.php` or :file:`AdditionalConfiguration.php`

.. _best-practice-boilerplate:

Use TYPO3’s Core Default.yaml as boilerplate
============================================

It is recommended to start by copying the file
:file:`typo3/sysext/rte_ckeditor/Configuration/RTE/Default.yaml` into your
sitepackage to the file
:file:`EXT:my_sitepackage/Configuration/RTE/MyConfiguration.yaml`.


Check TYPO3's Core Full.yaml to gain insight into a more extensive configuration
================================================================================

This preset shows more configured options and plugins. It is not intended for real use.
It acts as an example.

:file:`typo3/sysext/rte_ckeditor/Configuration/RTE/Full.yaml`


Use Core includes
=================

It is recommended to use the following includes at the top of your custom
configuration:

.. code-block:: yaml
   :caption: EXT:my_sitepackage/Configuration/RTE/MyConfiguration.yaml

   imports:
       - { resource: "EXT:rte_ckeditor/Configuration/RTE/Processing.yaml" }
       - { resource: "EXT:rte_ckeditor/Configuration/RTE/Editor/Base.yaml" }
       - { resource: "EXT:rte_ckeditor/Configuration/RTE/Editor/Plugins.yaml" }

If you started out by copying this extensions
:ref:`Default.yaml as boilerplate <best-practice-boilerplate>` the imports
should already be there.

The include files are already split up so the processing transformations can
just be included or even completely disabled (by removing the line for importing).

Please be aware that removing the :file:`Processing.yaml` removes
security measures. In that case you have to take care of keeping the ckeditor
safe yourself.
