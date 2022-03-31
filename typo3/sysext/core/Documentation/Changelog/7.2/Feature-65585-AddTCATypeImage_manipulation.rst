
.. include:: /Includes.rst.txt

================================================
Feature: #65585 - Add TCA type imageManipulation
================================================

See :issue:`65585`

Description
===========

TCA type `imageManipulation` brings a image manipulation wizard to the core.

This first version brings image cropping with the possibility to
set a certain aspect ratio for the cropped area. The
sys_file_reference.crop property is extended and can now also hold
a json string to describe the image manipulation.

The `LocalCropScaleMaskHelper` that is used by the core
to create adjusted images is also adjusted to handle the new format.


Impact
======

There is an new TCA type column type `imageManipulation` it supports the following config:

- file_field: string, default `uid_local`
- enableZoom: bool, default `FALSE`
- allowedExtensions: string, default `$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']`
- ratios: array, default

  - '1.7777777777777777' => '16:9',
  - '1.3333333333333333' => '4:3',
  - '1' => '1:1',
  - 'NaN' => 'Free',

When `ratios` is set in TCA the defaults are neglected.

Property `sys_file_reference.crop` can now hold a string representing a json object. `LocalCropScaleMaskHelper` checks
if the it can parse the string as json. If it can it assumes it holds the properties: `x`, `y`, `width` and `height`.


.. index:: TCA, LocalConfiguration, Backend
