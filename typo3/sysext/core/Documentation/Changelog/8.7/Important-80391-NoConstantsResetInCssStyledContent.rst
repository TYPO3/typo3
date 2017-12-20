.. include:: ../../Includes.txt

==========================================================================
Important: #80391 - Css Styled Content will not reset TypoScript Constants
==========================================================================

See :issue:`80391`

Description
===========

Previously the TypoScript definition from CSS Styled Content reset all
constants that were set before the static template was included to preserve
the namespace :typoscript:`styles.content`.

Since there is no need to reset the constants, this behaviour is removed.

Removed Code
------------

.. code-block:: typoscript

   # Clear out any constants in this reserved room!
   styles.content >

.. index:: TypoScript, Frontend, ext:css_styled_content
