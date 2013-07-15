.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



.. _definition-lists-creating:

Creating a definition list
^^^^^^^^^^^^^^^^^^^^^^^^^^


.. _case-1-nothing-is-highlighted-cursor-is-inside-p-or-hx:

Case 1: Nothing is highlighted, cursor is inside p or hx.
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Example:

<p>Definition term\|</p>

If dl-button is clicked, this becomes:

<dl>

<dt>Some Text\|</dt>

</dl>


.. _case-2-nothing-is-highlighted-cursor-is-inside-a-block-element-other-than-p-or-hx:

Case 2: Nothing is highlighted, cursor is inside a block element other than p or hx.
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Example:

<div>Definition term\|</div>

If dl-button is clicked, this becomes:

<div>

<dl>

<dt>Definition term\|</dt>

</dl>

</div>


.. _case-3-creating-a-dl-from-multiple-highlighted-paragraphs:

Case 3: Creating a dl from multiple highlighted paragraphs
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Let us say, we have the following code highlighted:

<p>Definition term 1</p>

<p>Definition text 1</p>

<p>Definition term 2</p>

<p>Definition text 2</p>

Klicking the dl-button results in alternatingdt/ddelements wrapped
bydl:

<dl>

<dt>Definition term 1</dt>

<dd>Definition text 1</dd>

<dt>Definition term 2</dt>

<dd>Definition text 2\|</dd>

</dl>


.. _case-4-creating-a-dl-from-a-highlighted-combination-of-hx-and-p:

Case 4: Creating a dl from a highlighted combination of hx and p
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

<h4>headline 1</h4>

<p>paragraph 1.1</p>

<p>paragraph 1.2</p>

<h4>headline 2</h4>

<p>paragraph 2.1</p>

In this case

- everyhxwould becomedt,and

- everypwould becomedd.

<dl>

<dt>headline 1</dt>

<dd>paragraph 1.1</dd>

<dd>paragraph 1.2</dd>

<dt>headline 2</dt>

<dd>paragraph 2.1\|</dd>

</dl>

