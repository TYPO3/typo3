
.. include:: ../../Includes.txt

=================================
Feature: #50136 - Add SVG support
=================================

See :issue:`50136`

Description
===========

Added rendering support for SVG images. When an SVG image is scaled there is no processed file created but only a
sys_file_processedfile record with the calculated new dimensions.

When a mask of explicit cropping is set for an SVG image, the a processed file is created like for all other images.

An extra fallback is added to ImageInfo to determine SVG dimensions when IM/GM fails. The new fallback reads the
contents of the SVG file as a normal XML file and tries to find width and height in the outer tag. When no
width and height are found viewBox is checked and when present the 3th and 4th value are used as width and height.


Impact
======

SVG is added as default supported image file extension to `$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']`.


.. index:: LocalConfiguration, FAL
