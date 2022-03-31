.. include:: /Includes.rst.txt

=================================================================================
Breaking: #79622 - Section Frame for CSS Styled Content replaced with Frame Class
=================================================================================

See :issue:`79622`

Description
===========

The functionality provided by `Section Frame` has been streamlined with Fluid Styled
Content and is now available as `Frame Class`. Previously, integers have been
stored in the Database that required a mapping of non speaking values.

The new introduced speaking values provide a more valuable use, for example
in Fluid Styled Content the values are used directly without mapping.

For CSS Styled Content the original behaviour has been mapped to the new keys in the
database and the option `invisible` has been dropped.


Compatibility Table
-------------------

===============   ===============   ===============   ===================================   =======================
Name              Previous Key      New Key           CSS Class                             Additional Effects
===============   ===============   ===============   ===================================   =======================
Default           0                 default           csc-frame csc-frame-default           -
Invisible         1                 (dropped)         -                                     -
Ruler Before      5                 ruler-before      csc-frame csc-frame-ruler-before      -
Ruler After       6                 ruler-after       csc-frame csc-frame-ruler-after       -
Indent            10                indent            csc-frame csc-frame-indent            -
Indent, 33/66%    11                indent-left       csc-frame csc-frame-indent-left       -
Indent, 66/33%    12                indent-right      csc-frame csc-frame-indent-right      -
No Frame          66                none              (none)                                No Frame is rendered
===============   ===============   ===============   ===================================   =======================


TypoScript Before
-----------------

.. code-block:: typoscript

   tt_content.stdWrap.innerWrap.cObject.key.field = section_frame
   tt_content.stdWrap.innerWrap.cObject.5 =< tt_content.stdWrap.innerWrap.cObject.default
   tt_content.stdWrap.innerWrap.cObject.5.20.10.value = csc-frame csc-frame-ruler-before


TypoScript After
----------------

.. code-block:: typoscript

   tt_content.stdWrap.innerWrap.cObject.key.field = frame_class
   tt_content.stdWrap.innerWrap.cObject.ruler-before =< tt_content.stdWrap.innerWrap.cObject.default
   tt_content.stdWrap.innerWrap.cObject.ruler-before.20.10.value = csc-frame csc-frame-ruler-before


Affected Installations
======================

Installations that use the CSS Styled Content.


Migration
=========

Default fames can be automatically upgraded to the new field and values. Custom
values will be prefixed with `custom-<key>` and will also be transferred to the
new field.

Note that custom values must be added again to the field configuration, and the
mapping in the TypoScript rendering definition for CSS Styled Content needs also
to adapt.


Add custom frame
----------------

.. code-block:: typoscript

   TCEFORM.tt_content.frame_class {
      addItems {
         custom-1 = LLL:EXT:extension/Resources/Private/Language/locallang.xlf:customFrame
      }
   }

.. code-block:: php

   $GLOBALS['TCA']['tt_content']['columns']['frame_class']['config']['items'][] = [
      0 = LLL:EXT:extension/Resources/Private/Language/locallang.xlf:customFrame
      1 = custom-1
   ];


Adapt rendering definition
--------------------------

.. code-block:: typoscript

   tt_content.stdWrap.innerWrap.cObject.custom-1 =< tt_content.stdWrap.innerWrap.cObject.default
   tt_content.stdWrap.innerWrap.cObject.custom-1.20.10.value = csc-frame csc-frame-custom-1


.. index:: Frontend, TypoScript, ext:css_styled_content
