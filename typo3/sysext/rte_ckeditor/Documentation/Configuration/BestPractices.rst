.. include:: /Includes.rst.txt


.. _config-best-practices:

============================
Configuration Best Practices
============================


.. _best-practice-sitepackage:

Use a Sitepackage Extension
===========================

It is generally recommended to use a a sitepackage extension to
customize a TYPO3 website. This will contain configuration files
for the site.

For more information about sitepackages in TYPO3 see:

* Benjamin Kott: `"The Anatomy of Sitepackages"
  <https://www.slideshare.net/benjaminkott/typo3-the-anatomy-of-sitepackages>`__ (Slideshare)
* :doc:`t3sitepackage:Index`

.. TODO Explain Create an extension, add ext_localconf.php, start over and copy over the Default.yaml file

Use TYPO3’s Core Default.yaml as boilerplate
============================================

Instead of starting from scratch when writing custom configurations, it is
recommended to copy TYPO3’s configuration file
:file:`typo3/sysext/rte_ckeditor/Configuration/RTE/Default.yaml`
into your extension folder :file:`<extkey>/Configuration/RTE/`.

Check TYPO3's Core Full.yaml to gain insight into a more extensive configuration
================================================================================

This preset shows more configured options and plugins. It is not intended for real use.
It acts as an example.

:file:`typo3/sysext/rte_ckeditor/Configuration/RTE/Full.yaml`


Use Core Includes
=================

.. todo: clarification needed: add examples, explanation

The base processing configuration for “transformations” (key “processing”)
is written in a way that is restrictive on the one hand, but also allows
to be extended.

The include files are already split up so transformations can just be included
or even completely disabled (by removing the line for importing) to have CKEditor
take care of all security measures.

