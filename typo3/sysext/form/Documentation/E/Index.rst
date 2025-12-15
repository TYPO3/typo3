.. include:: /Includes.rst.txt

.. _forEditors:

===========
For Editors
===========

Introduction to forms
---------------------

Forms are provided by the **Form TYPO3 extension**. Form is a TYPO3 core
extension which has been available by default since TYPO3 version 8.

What can Form do?
-----------------

- TYPO3 core extension
- flexible, extensible and easy to use
- easy to use via drag-and-drop
- live preview
- form reuse
- create templates for new forms
- set mandatory fields
- set finishers (downstream processing)
- automatic spam protection
- multi-step forms

What can't Form do?
-------------------

Form has limited:

- formatting of form labels
- multilingual support
- possibilities for textual design of emails

When can I use Form?
--------------------

- for contact forms
- for application forms
- for simple or complex forms
- for different forms on my pages

Notes on data protection
------------------------

Data submitted in forms is not stored in the TYPO3 backend due to privacy reasons.
There are TYPO3 extensions that retrofit this behavior but we do not recommend using these
extensions. Instead, check if the form data can be transferred directly to your
CRM or similar tools.

..  toctree::
    :maxdepth: 1

    FormElements/Index
    Validators/Index
    Finishers/Index
    Accessibility/Index
    Tutorials/Index
