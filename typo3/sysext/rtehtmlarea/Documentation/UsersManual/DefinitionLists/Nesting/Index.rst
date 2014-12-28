.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



.. _definition-lists-nesting:

Nesting definition lists (Indent/TAB)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Whiledtdoes not allow any blockelements inside,dddoes, and you may
also nest definition lists.

This is useful if you have a term that has different definitions in
different contexts. The nesting can be achieved using indent/outdent
buttons as for the other list types.


.. _case-1-indenting-without-any-highlighted-text:

Case 1: Indenting without any highlighted text
""""""""""""""""""""""""""""""""""""""""""""""

Let us say I want to talk about the Acronym PC in different context:

.. code-block:: html

	<dl>
		<dt>PC</dt>
		<dd>The Acronym has different meanings in different contexts:\|</dd>
	</dl>


Now I press indent, with the result that a new combination of dl+dt is
created at my cursor position and my cursor is inside the newly
created dt:

.. code-block:: html

	<dl>
		<dt>PC</dt>
		<dd>The Acronym has different meanings in different contexts:
			<dl>
				<dt>\|</dt>
			</dl>
		</dd>
	</dl>


.. _case-2-indenting-highlighted-dt-dd-elements:

Case 2: Indenting highlighted dt/dd elements
""""""""""""""""""""""""""""""""""""""""""""

This case is divided into two subcases.


.. _subcase-2a-we-have-a-dd-as-previous-sibling-of-the-hightlighted-elements:

Subcase 2a: We have a dd as previous sibling of the hightlighted elements.
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

In this case the highlighteddt/ddelements should be wrapped
with :code:`<dl>` and should be placed inside theddat cursor position. Example:

.. code-block:: html

	<dl>
		<dt>PC</dt>
		<dd>The Abbreviation has different meaning in different contexts:</dd>
		<dt>Information technology</dt>
		<dd>Personal Computer</dd>
		<dt>Social sciences</dt>
		<dd>Political correctness</dt>
		<dt>Latin Grammar</dt>
		<dd>Participium coniunctum</dd>
	</dl>


Clicking indent results in:

.. code-block:: html

	<dl>
		<dt>PC</dt>
		<dd>The Abbreviation has different meaning in different contexts:
		<dl>
			<dt>Information technology</dt>
			<dd>Personal Computer</dd>
			<dt>Social sciences</dt>
			<dd>Political correctness</dt>
			<dt>Latin Grammar</dt>
			<dd>Participium coniunctum\|</dd>
		</dl>
		</dd>
	</dl>


.. _subcase-2b-we-have-a-dt-as-previous-sibling-of-the-highlighted-dt-dd-elements:

Subcase 2b: We have a dt as previous sibling of the highlighted dt/dd elements.
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

In this subcase the highlighted elements will be wrapped by dd+dl:

.. code-block:: html

	<dl>
		<dt>PC</dt>
		<dt>Information technology</dt>
		<dd>Personal Computer</dd>
		<dt>Social sciences</dt>
		<dd>Political correctness</dt>
		<dt>Latin Grammar</dt>
		<dd>Participium coniunctum</dd>
	</dl>


Clicking indent will result in:

.. code-block:: html

	<dl>
		<dt>PC</dt>
		<dd>
			<dl>
				<dt>Information technology</dt>
				<dd>Personal Computer</dd>
				<dt>Social sciences</dt>
				<dd>Political correctness</dt>
				<dt>Latin Grammar</dt>
				<dd>Participium coniunctum|</dd>
			</dl>
		</dd>
	</dl>


.. _case-3-outdenting-without-any-highlighting:

Case 3: Outdenting without any highlighting
"""""""""""""""""""""""""""""""""""""""""""

After having said all about PC, I want to talk about RTE on the outer
level and therefore do the following:

- I press Enter to create a new dt and perhaps klick dt/dd-toggler.

- I click outdent or press Shift+TAB to outdent the newly created dt.

As a result, my newly created dt/dd element is moved beneath the
nested dl.

Example:

.. code-block:: html
   :emphasize-lines: 11,11

	<dl>
		<dt>PC</dt>
		<dd>The Abbreviation has different meaning in different contexts:
		<dl>
			<dt>Information technology</dt>
			<dd>Personal Computer</dd>
			<dt>Social sciences</dt>
			<dd>Political correctness</dt>
			<dt>Latin Grammar</dt>
			<dd>Participium coniunctum</dd>
			<dt> | </dt>
		</dl>
		</dd>
	</dl>


Clicking outdent in this situation will result in:

.. code-block:: html
   :emphasize-lines: 13,13

	<dl>
		<dt>PC</dt>
		<dd>The Abbreviation has different meaning in different contexts:
		<dl>
			<dt>Information technology</dt>
			<dd>Personal Computer</dd>
			<dt>Social sciences</dt>
			<dd>Political correctness</dt>
			<dt>Latin Grammar</dt>
			<dd>Participium coniunctum</dd>
		</dl>
		</dd>
		<dt>|</dt>
	</dl>


.. _case-4-outdenting-a-highlighted-group-of-dt-dd-elements:

Case 4: Outdenting a highlighted group of dt/dd-elements
""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Outdenting of highlighted elements only works in a sensible way, if
the highlighted elements are at the very end of an indented dl. If you
want to move up or down elements at the beginning or in the middle,
this can be achieved with cut and paste.

Example: Highlighted elements are at the end of a nested dl

.. code-block:: html

	<dl>
		<dt>outer term 1</dtd>
		<dd>outer data 1
		<dl>
			<dt>inner term 1</dt>
			<dd>inner data 1</dd>
			<dt>inner term 2</dt>
			<dd>inner data 2</dd>
			<dt>inner term 3</dt>
			<dd>inner data 3</dd>
		</dl>
		</dd>
	</dl>


Clicking outdent will place the selected elements below the next outer
dd:

.. code-block:: html
   :emphasize-lines: 11,12

	<dl>
		<dt>outer term 1</dtd>
		<dd>outer data 1
		<dl>
			<dt>inner term 1</dt>
			<dd>inner data 1</dd>
			<dt>inner term 2</dt>
			<dd>inner data 2</dd>
		</dl>
		</dd>
		<dt>inner term 3</dt>
		<dd>inner data 3 |  </dd>
	</dl>

