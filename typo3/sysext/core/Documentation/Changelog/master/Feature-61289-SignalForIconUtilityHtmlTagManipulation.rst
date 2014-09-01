==============================================================
Feature: #61289 - Signal for IconUtility html tag manipulation
==============================================================

Description
===========

This signal allows to manipulate the rendered html code for a sprite icon by an extension.

Currently all sprite icons are rendered as
:code: <span class="">&nbsp;</span>

Extensions can now adjust the html tag, add or remove attributes and define own content in between the html tags.

Impact
======

The rendered html code is not stuck to a span with fixed classes anymore, but can be modified by an extension.
