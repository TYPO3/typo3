.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



.. _definition-lists-remapping:

Creating and remapping dt/dd elements
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


.. _case-1-behaviour-of-enter-key-if-the-cursor-is-at-the-end-of-dt-dd:

Case 1: Behaviour of Enter key if the cursor is at the end of dt/dd
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

- If the cursor is at the end of adt-element and Enter is pressed, a
  newdd-Element will be created as next sibling.

- If the cursor is at the end of add-element and Enter is pressed, a
  newdt-Element will be created as next sibling.


.. _case-2-behaviour-of-enter-key-if-the-cursor-is-at-the-beginning-of-dt-dd:

Case 2: Behaviour of Enter key if the cursor is at the beginning of dt/dd
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

- If the cursor is at the beginning of a dt-Element and Enter is
  pressed, a new dt will be created as previous sibling.

- If the cursor is at the beginning of a dd-Element and Enter is
  pressed, a new dd will be created.


.. _case-3-remapping-dd-to-dt:

Case 3: Remapping dd to dt
""""""""""""""""""""""""""

In order to remap thedttodd, the cursor has to be somewhere inside a
dt/dd. The remapping is done by clicking the dt/dd-toggle button.

