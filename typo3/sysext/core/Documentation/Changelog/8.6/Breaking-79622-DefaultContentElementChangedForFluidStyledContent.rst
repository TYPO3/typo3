.. include:: /Includes.rst.txt

===========================================================================
Breaking: #79622 - Default content element changed for Fluid Styled Content
===========================================================================

See :issue:`79622`

Description
===========

The default content element has been streamlined with CSS Styled Content
and has been changed to "Text".


Impact
======

The default content element is now "Text".


Affected Installations
======================

All instances that have Fluid Styled Content installed.


Migration
=========

To restore the configuration you need to set the default content element
manually to your preferred choice. You can do this by simply overriding
the configuration again in your `Configuration/TCA/Overrides/tt_content.php` file.

.. code-block:: php

   $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['default'] = 'textmedia';

.. code-block:: php

   $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['default'] = 'header';


.. index:: TCA, ext:fluid_styled_content
