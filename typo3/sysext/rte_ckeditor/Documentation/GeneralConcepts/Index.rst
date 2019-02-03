.. include:: ../Includes.txt


.. _general-concepts:

================
General Concepts
================

Editing Modes
=============

CKEditor has three editing modes:

* **“Article Editor”** mode (classic RTE): Editing is done within a fixed container.
  It is possible to customize how the editor behaves and how the content is styled.
* **“Document Editor”** mode: Editing is done as in Microsoft Word or Google Docs,
  where the document itself mimics a sheet of paper. The focus is on structuring
  content and not mainly on the layout itself.
* **“Inline Editor”** mode: All formatting styles are reused from the surrounding
  HTML and CSS styles, allowing for a seamless frontend editing.

“Article Editor” mode is used in the TYPO3 Backend.

“Inline Editor mode” is used by TYPO3’s “frontend_editing” which can be found on GitHub
(`frontend_editing <https://github.com/FriendsOfTYPO3/frontend_editing>`__.
Frontend_editing is not covered in this document.

For a demonstration of all three modes, see the
`CKEditor demo <https://ckeditor.com/ckeditor-4/demo/#article>`__.
