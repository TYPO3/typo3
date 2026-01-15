..  include:: /Includes.rst.txt

..  _feature-108648-1770305451:

===================================================================
Feature: #108648 - Option to modify src attribute for Vimeo/YouTube
===================================================================

See :issue:`108648`

Description
===========

The new configuration option `srcAttribute` for the `YouTubeRenderer`
and `VimeoRenderer` can be used to adjust the previously hard-coded `src`
attribute in the generated iframe HTML code. This can be helpful if
iframes shouldn't be loaded immediately because of privacy concerns,
in which case an alternative such as `data-src` can be used in the
initial HTML markup.

Example:

..  code-block:: html

    <f:media
        file="{youtubeVideo}"
        additionalConfig="{srcAttribute: 'data-src'}"
    />


Impact
======

The `src` attribute for YouTube and Vimeo embeds can now be renamed.

..  index:: Frontend, ext:core
