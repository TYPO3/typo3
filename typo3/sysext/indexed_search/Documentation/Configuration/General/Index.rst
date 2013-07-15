.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _configuration-general:

General
^^^^^^^

The most basic requirement for the search engine to work is that pages
are getting indexed. That will not happen by just installing the
plugin! You will have to set up in TypoScript that a certain page
should be indexed. That is needed for several good reasons. First of
all not all sites in a TYPO3 database might need indexing. So
therefore we disable it on a per-site basis. Secondly a single site
may have frames and in that case we need only index the page-object
which actually shows the page content.

Lets say that you have a PAGE object called "page" (that is pretty
typical), then you will have to set this config-option:

.. code-block:: typoscript

   page.config.index_enable = 1

When this option is set you should begin to see your pages being
indexed when they are shown next time. Remember that only cached pages
are indexed!

This is documented in :ref:`CONFIG section of the TSref <t3tsref:config>`. Please look there
for further options. For instance indexing of external media can also
be enabled there.


.. _configuration-languages:

Languages
"""""""""

The plugin supports all system languages in TYPO3. Translation is done
using the typo3.org tools.

If you want to use eg. danish language that will automatically be used
if this option is set in your template (the value is the internal
language key):

.. code-block:: typoscript

   config.language = dk

