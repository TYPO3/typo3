.. include:: /Includes.rst.txt

.. _feature-93942-1709722341:

==========================================
Feature: #93942 - Crop SVG images natively
==========================================

See :issue:`93942`

Description
===========

Cropping SVG images via backend image editing or specific Fluid ViewHelper via
:html:`<f:image>` or :html:`<f:uri.image>` (via :html:`crop` attribute) now
outputs native SVG files by default - which are processed but again stored
as SVG, instead of rasterized PNG/JPG images like before.


Impact
======

Editors and integrators can now crop SVG assets without an impact to their
output quality.

Forced rasterization of cropped SVG assets can still be performed by setting the
:html:`fileExtension="png"` Fluid ViewHelper attribute or the TypoScript
:typoscript:`file.ext = png` property.

:html:`<f:image>` ViewHelper example:
-------------------------------------

..  code-block:: html

    <f:image image="{image}" fileExtension="png" />

This keeps forcing images to be generated as PNG image.

`file.ext = png` TypoScript example:
------------------------------------

..  code-block:: typoscript

    page.10 = IMAGE
    page.10.file = 2:/myfile.svg
    page.10.file.crop = 20,20,500,500
    page.10.file.ext = png

If no special hard-coded option for the file extension is set, SVGs are now
processed and stored as SVGs again.

.. index:: Backend, FAL, Fluid, Frontend, TypoScript, ext:fluid
