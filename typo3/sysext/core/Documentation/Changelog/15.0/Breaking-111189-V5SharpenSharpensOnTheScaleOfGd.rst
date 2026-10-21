..  include:: /Includes.rst.txt

..  _breaking-111189-1792582783:

============================================================
Breaking: #111189 - v5_sharpen() sharpens on the scale of GD
============================================================

See :issue:`111189`

Description
===========

:php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->v5_sharpen()` returns
the ImageMagick and GraphicsMagick command for the scale GIFBUILDER
sharpens on with GD. A strength of 1 to 100 falls into ten even steps of
10, and step n returns `-unsharp 1x2+(n*0.25)+0` instead of `-sharpen 1x2`
to `-sharpen 5x5`.
Step 1 is what :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['sharpen']` applies
to scaled-down images. The protected property
:php:`GraphicalFunctions->im5fx_sharpenSteps` holds the `-unsharp` geometry
of each step accordingly.

GD sharpens a little more at each step, so that the `sharpen` value of an
`EFFECT` object and `niceText.sharpen` in GIFBUILDER look as they do with
ImageMagick and GraphicsMagick.

Impact
======

Extensions that call :php:`v5_sharpen()` get an `-unsharp` command. Low
strengths sharpen much less than the former `-sharpen` command did, values
from 80 up about as much.

GIFBUILDER images that use `sharpen` are sharpened an eighth more once they
are generated again.

Affected installations
======================

Extensions that call :php:`GraphicalFunctions->v5_sharpen()` or set
:php:`GraphicalFunctions->im5fx_sharpenSteps`. The extension scanner reports
the calls and the property access, not a subclass that overrides the
property.

Installations that use `sharpen` in an `EFFECT` object or as
`niceText.sharpen` of a GIFBUILDER `TEXT` object.

Migration
=========

An extension that needs the former result passes `-sharpen` with the
geometry it wants to ImageMagick or GraphicsMagick itself.

An extension that sets :php:`im5fx_sharpenSteps` lists ten `-unsharp`
geometries, for example `1x2+0.25+0`, instead of `-sharpen` geometries.

..  index:: Frontend, PHP-API, PartiallyScanned, ext:core
