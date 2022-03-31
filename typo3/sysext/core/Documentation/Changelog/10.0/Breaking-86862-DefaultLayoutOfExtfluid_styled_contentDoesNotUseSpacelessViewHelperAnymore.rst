.. include:: /Includes.rst.txt

=======================================================================================================
Breaking: #86862 - Default Layout of ext:fluid_styled_content does not use spaceless viewHelper anymore
=======================================================================================================

See :issue:`86862`

Description
===========

The default layout file of ext:fluid_styled_content removed all white space characters in the whole output, which led
to occasional issues with the generated markup. This general removal of whitespace characters has been removed.
It is in the hand of the integrator to apply white space character removal on their own on sensible places using template override functionality.


Impact
======

Markup of pages rendered using ext:fluid_styled_content will contain more white space characters.
This might influence the visual output.


Affected Installations
======================

Each instance using ext:fluid_styled_content as rendering template.


Migration
=========

Review and adjust the markup generated for your front end. In case you did not experience any issues before,
you can override the default template and reintroduce the spaceless viewHelper, or apply it in other sections of the output where it will be helpful.

.. index:: Fluid, Frontend, RTE, NotScanned, ext:fluid_styled_content
