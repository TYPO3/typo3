.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



.. _formatting-drop-down-behavior:

Behavior of the text formating drop-down list and of the buttons
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

While in case of nested inline markup all buttons may be highlighted,
only one of the options will be pre-selected in the drop-down list and
therefore be shown in the collapsed drop-down list.


.. _formatting-legend:

Legend:
"""""""

highlighted (selected) string:This is the string inside the textarea
the author has selected using the mouse or keyboard.

\|= cursor position inside the textarea.


.. _formatting-case-1-no-string-is-highlighted-and-the-cursor-is-outside-any-inline-element:

Case 1: No string is highlighted and the cursor is outside any inline element:
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

a) the option shown in the collapsed drop-down list is 'No text format
**';**

b) the drop-down list and all buttons are disabled.


.. _formatting-case-2-no-string-is-highlighted-and-the-cursor-is-inside-an-inline-element:

Case 2: No string is highlighted and the cursor is inside an inline element:
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Example:<code>wor\|d</code>

a) the option shown in the collapsed drop-down list is the type of the
inner inline element in which the cursor is positioned;

b) the drop-down list and all buttons are enabled;

c) the button corresponding to the type of the inner inline element in
which the cursor is positioned is highlighted by means of a white
background; if the cursor is positioned inside nested inline elements,
all corresponding buttons are highlighted by means of a white
background;

d) if the author chooses a different markup in the drop-down list or
clicks on a button that is not highlighted, the markup of the inner
inline element is remapped;

e) if the author chooses 'Remove text format', the markup of the inner
inline element is removed;

f) if the author clicks on an highlighted button, the markup of the
innermost inline element of the corresponding type is removed;

g) the position of the cursor is unchanged.


.. _formatting-case-3-a-string-is-highlighted-and-crosses-multiple-elements:

Case 3: A string is highlighted and crosses multiple elements:
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

a) the option shown in the collapsed drop-down list is 'No text
format' (first option);

b) if the string crosses multiple block elements:

- the drop-down list is disabled;

c) if the string crosses multiple inline elements:

- the option shown in the collapsed drop-down list is 'No text format';

- the drop-down list is enabled;

- if the author chooses a markup, the highlighted string is wrapped with
  the chosen markup;

- the resulting marked up string is not highlighted;

- the cursor is positioned at the end of the marked up string.


.. _formatting-case-4-the-highlighted-string-is-not-contained-in-any-inline-element:

Case 4: The highlighted string is not contained in any inline element:
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

a) the option shown in the collapsed drop-down list is 'No text
format';

b) if the author chooses a markup, the highlighted string is wrapped
with the chosen markup;

c) the resulting marked up string is not highlighted;

d) the cursor is positioned at the end of the marked up string, so
that This is great!becomes <strong> This is great!\|</strong>.


.. _formatting-case-5-the-highlighted-string-is-contained-in-an-inline-element:

Case 5: The highlighted string is contained in an inline element:
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

a) the option shown in the collapsed drop-down list is 'No text
format';

b) if the author chooses a markup, the highlighted string is wrapped
with the chosen markup;

c) the resulting marked up string is not highlighted;

d) the cursor is positioned at the end of the marked up string.

Example 1::

	<q>This is a verygood question.</q>

becomes::

	<q>This is a <strong> very\|</strong>question.</q>.

Example 2::

	<q>This is great!</q>, he shouted.

becomes::

	<q><strong>This is great!\|</strong></q>, he shouted.


.. _formatting-case-6-the-highlighted-string-contains-exactly-the-complete-inline-element:

Case 6: The highlighted string contains exactly the complete inline element:
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Example of highlighted string: :code:`<code>word</code>`

a) The option shown in the collapsed drop-down list is the type of
inline element corresponding to the highlighted string;

b) if the author chooses 'Remove textformat', the inline markup gets
removed: :code:`<code>word</code>becomesword\|`;

c) if the author chooses another markup, e.g. :code:`<var>`, the markup wraps
the highlighted node, so
that :code:`<code>word</code>becomes<var><code>word\|</code></var>`.

