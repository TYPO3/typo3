.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _extension-configuration:

Extension configuration variables
---------------------------------

The Extension Manager installation dialog allows to set the following
extension configuration variables:

- **Default configuration settings:** let you choose a set of default
  Page TSconfig and User TSconfig settings; select **Typical (Most
  commonly used features are enabled. Select this option if you are
  unsure which one to use.)** for the typical site requirements; select
  **Minimal (Most features disabled. Administrator needs to enable them
  using TypoScript. For advanced administrators only.)** for minimal
  settings; select **Demo (Show-off configuration. Includes pre-
  configured styles. Not for production environments.)** to explore some
  of the available features; default value is Typical;

- **Enable images in the RTE** : if this boolean variable is set, the
  use of images in the "Minimal" or the "Typical" default configuration
  of the RTE will be enabled; default value is 0;

- **Enable additional inline elements:** If set, the potential use of
  additional inline elements will be enabled; default value is 0;

- **Enable features that use the style attribute** : If set, the
  potential use of features that use the style attribute (color,
  fontstyle, fontsize) will be enabled; default value is 1;

- **Enable links accessibility icons** : if this boolean variable is
  set, accessibility icons may be added to links; default value is 0;
  see Page TSconfig property RTE.classesAnchor;

- **Enable compressed scripts:** if this boolean variable is set, editor
  scripts are compressed; default value is 1.

If the SpellChecker is not enabled, then the remaining configuration
variables are irrelevant; note that if extension static\_info\_tables
is not installed, SpellCheker will not be enabled.

- **No spell checking languages** : the list of languages for which
  Aspell does not provide spell checking (see `Unsupported Languages
  <http://aspell.net/man-html/Unsupported.html#Unsupported>`_ ) and for
  which the Spell Checker feature will therefore be disabled (not shown
  in the RTE tool bar); default value is 'ja, km, ko, lo, th, zh, b5,
  gb';

- **Aspell directory:** the server directory in which Aspell is
  installed; default value is "/usr/bin/aspell";

- **Force Aspell command mode:** if this boolean variable is set,the
  Aspell command interface will be used; this may be useful when PHP is
  compiled with pspell, but with an old version of Aspell, and a newer
  version is available in another directory; default value is 0.


