.. include:: /Includes.rst.txt

===================================================
Feature: #87525 - Add api=1 option in VimeoRenderer
===================================================

See :issue:`87525`

Description
===========

The parameter api=1 in Vimeo video urls allows API interactions with the video player,
for example adding a button to interact with a video on your page.
The configuration now allows setting this parameter when rendering Vimeo videos in TYPO3.

Impact
======

Setting the parameter :typoscript:`api = 1` either in TypoScript or Fluid will append :html:`api=1` to the Vimeo video URL.

Usage
=====

Set the parameter via TypoScript for EXT:fluid_styled_content by using:

.. code-block:: typoscript

   lib.contentElement.settings.media.additionalConfig.api = 1

When using Fluid use the Fluid media ViewHelper and :html:`additionalConfig` to set the argument:

.. code-block:: html

   <f:media
         file="{file}"
         alt="{file.properties.alternative}"
         title="{file.properties.title}"
         additionalConfig="{api: 1}"
   />


.. index:: Fluid, TypoScript, ext:fluid_styled_content
