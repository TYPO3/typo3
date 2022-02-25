.. include:: /Includes.rst.txt


.. _forEditors:

===========
For Editors
===========

Introduction to forms
---------------------

Forms are managed with the **TYPO3 extension "Form "**. Form is a TYPO3 core
extension, which is available by default since TYPO3 version 8.

What can Form do?
-----------------

- TYPO3 core extension
- flexible, extensible and easy to use
- easy working via drag-and-drop
- live preview of forms
- reuse of forms
- create templates for new forms
- Set mandatory fields
- Set different finishers (downstream processes) for forms
- Automatic protection against spam
- multi-step forms

What can't Form do?
-------------------

Editors reach their limits with Form in certain use cases.

- limited formatting of form labels
- limited support of multilingualism
- limited possibilities for textual design of sent mails

When do I use Form?
-------------------

- for contact forms
- for application forms
- when I want to use simple or special forms
- when I want to use different forms on my pages

Notes on data protection
------------------------

Sent forms are not stored in the TYPO3 backend for privacy reasons. There are
TYPO3 extensions that retrofit such behavior. We recommend not to use such
extensions. Instead, you should check if the form data can be transferred
directly to your CRM or similar tools.

.. toctree::
   :maxdepth: 1

   Tutorials/Index
   FormElements/Index
   Validators/Index
