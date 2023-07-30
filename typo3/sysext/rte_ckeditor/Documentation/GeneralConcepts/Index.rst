.. include:: /Includes.rst.txt


.. _general-concepts:

================
General Concepts
================

User interfaces
===============

CKEditor has multiple user interfaces, of which TYPO3 uses the following:

Classic
   Editing is done within a fixed container.
   It is possible to customize how the editor behaves and how the content is styled.

   Used in the TYPO3 Backend.

Inline
   All formatting styles are reused from the surrounding HTML and CSS styles,
   allowing for a seamless frontend editing.

   Used by TYPO3’s frontend_editing,
   which can be found on `GitHub <https://github.com/FriendsOfTYPO3/frontend_editing>`__.
   frontend_editing is not covered in this document.

For a demonstration of all user interfaces,
see the `CKEditor demo <https://ckeditor.com/ckeditor-5/demo/editor-types/>`__.
