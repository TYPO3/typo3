..  include:: /Includes.rst.txt

..  _feature-82712-1790300000:

=====================================================================
Feature: #82712 - Display aspect ratio of cropped area in image editor
=====================================================================

See :issue:`82712`

Description
===========

The image manipulation dialog shows the size of the selected crop area. If
the aspect ratio "Free" is selected and the size corresponds to a common
aspect ratio such as `16:9`, `4:3` or `1:1`, this ratio is now displayed next
to the size, for example `1600×900 px (16:9)`.

If the size only approximately matches a common aspect ratio, which is typical
for a freely selected crop area, the ratio is prefixed with a tilde, for
example `635×849 px (~3:4)`. A deviation of up to 2% is tolerated. If no
common ratio is close enough, only the size is displayed as before.

Impact
======

Editors can see the proportions of a freely selected crop area without
calculating them. With a fixed aspect ratio the display is unchanged.

..  index:: Backend, JavaScript, ext:backend
