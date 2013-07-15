.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _formating-inline-elements:

Text formating with inline elements
-----------------------------------

The overall idea behind this is to make it as comfortable as possible
to write semantic inline markup without having to code. Buttons in
general are very easy to access, but if there are too many of them,
they are confusing.

Now, which elements you need most often depends on the type of text
you are writing.

- If your site is about literature, you need a lot of q, cite, samp,
  dfn.

- If your site is about typo3, what you need is a lot of code, var, kbd,
  samp, dfn and and ocassionally q and cite.

- On a university website you have both subjects in different parts of
  the pagetree.

Therefore the idea is to have those elements as buttons you need most
often, and the complete list in a drop-down list. Which buttons are
shown and which elements are shown are configurable via Page and/or
User TSconfig.


.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   ElementsList/Index
   DropDownLabels/Index
   DropDownBehavior/Index

