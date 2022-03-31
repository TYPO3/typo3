.. include:: /Includes.rst.txt

=================================================================================
Feature: #90994 - Mark current page in fluid_styled_content menu content elements
=================================================================================

See :issue:`90994`

Description
===========

All menu content elements related to page navigation reflect the "current"
state of a page now.

The resulting HTML of these page link lists is then:

.. code-block:: html

   <li>
       <a aria-current="page" > ...
   </li>

Impact
======

The aria attribute aria-current="page" is added to a-tag of a menu-item.

For styling with CSS the attribute of the link can be used:

.. code-block:: css

   [aria-current="page"] {
       /* Special style for the current page link */
   }
   [aria-current="page"]:hover {
       /* Special style for the current page link when hovered */
   }
   [aria-current="page"]::before {
       /* Special virtual element for additions like chevrons, etc. */
   }

.. index:: Frontend, ext:fluid_styled_content
