.. include:: /Includes.rst.txt

.. _important-97809-1656679033:

=============================================
Important: #97809 - Update @typo3.icons to v3
=============================================

See :issue:`97809`

Description
===========

The TYPO3 Icon set in version 3.x introduced a change in icon scaling.
Instead of having the default size fixed to a size, it's now scaling
according to the font-size where the icon is used.

The size previously represented by the identifier "default" is
now called "medium" to better reflect the scaling increase, resulting
in the fixed sizes now named "small", "medium" and "large".

How sizes can now be translated:

- "default" -> 1em, to scale with font size
- "small" -> fixed to 16px
- "medium" -> fixed to 32px
- "large" -> fixed to 64px

The TYPO3 Icon API previously set to :php:`Icon::SIZE_DEFAULT` by default and was
adapted to now use :php:`Icon::SIZE_MEDIUM` instead. That means there is no
change in behaviour except you explicitly called the icon API with the
size "default".

You should now see the icon scaling to the font size instead of set it to 32px.
To change it back to a fixed size, use the "medium" sized variant.

.. index:: Backend, ext:backend
