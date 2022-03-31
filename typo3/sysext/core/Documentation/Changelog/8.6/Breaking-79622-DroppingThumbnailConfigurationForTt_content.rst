.. include:: /Includes.rst.txt

==================================================================
Breaking: #79622 - Dropping thumbnail configuration for tt_content
==================================================================

See :issue:`79622`

Description
===========

It is currently not possible to set the thumbnail field depending on
the type of the record. Since tt_content uses different fields to store
media we are removing this default configuration that was set to `images`
by CSS Styled Content and `assets` by Fluid Styled Content.


Impact
======

Thumbnails in list view are no longer displayed for tt_content records.


Affected Installations
======================

All instances.


Migration
=========

To restore the configuration you need to set the thumbnail field manually to
your preferred choice.
You can do this by simply adding the configuration again in your `Configuration/TCA/Overrides/tt_content.php` file.

.. code-block:: php

   $GLOBALS['TCA']['tt_content']['ctrl']['thumbnail'] = 'image';

.. code-block:: php

   $GLOBALS['TCA']['tt_content']['ctrl']['thumbnail'] = 'assets';


.. index:: TCA, Backend, ext:fluid_styled_content, ext:css_styled_content
