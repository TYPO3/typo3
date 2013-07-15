.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



.. _styling-drop-down-behavior:

Behavior of the text styling drop-down list
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Only one of the classes is pre-selected in the Text Style selectlist
and therefore shown in the collapsed selectlist. At all times, only
classes allowed by the configuration settings are presented in the
selectlist.


.. _styling-legend:

Legend:
"""""""

highlighted (selected) string:This is the string inside the textarea
the user has selected using the mouse or keyboard.

\|= cursor position inside the textarea.


.. _styling-case-1-no-string-is-highlighted-and-the-cursor-is-outside-any-inline-element:

Case 1: No string is highlighted and the cursor is outside any inline element:
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

a) the option shown in the collapsed selectlist is 'No text style';

b) the selectlist is disabled.


.. _styling-case-2-no-string-is-highlighted-and-the-cursor-is-inside-an-inline-element:

Case 2: No string is highlighted and the cursor is inside an inline element:
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Example:<code>wor\|d</code>

a) the option shown in the collapsed selectlist is the current value
of the element's class attribute, or 'No text style' when the inline
element bears no class attribute;

b) the selectlist is enabled and contains the classes allowed for the
type of inline element;

c) if the author chooses a different class, the class attribute gets
updated; the position of the cursor is unchanged;

d) if the author chooses 'No text style', the current class is
removed; if the element has no more class, the class attribute gets
removed; if the element is a span element and it has no more
attribute, the span element is removed;the position of the cursor is
unchanged.


.. _styling-case-3-a-string-is-highlighted-and-crosses-multiple-elements:

Case 3: A string is highlighted and crosses multiple elements:
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

a) the option shown in the collapsed selectlist is 'No text style';

b) if the string crosses multiple block elements:

- the selectlist is disabled;

c) if the string crosses multiple inline elements:

- the selectlist is enabled and contains the classes allowed for the
  'span' element;

- if the author chooses a class, the highlighted string is wrapped with
  a 'span' element with the chosen class as value of its class
  attribute;

- the resulting marked up string is not highlighted;

- the cursor is positioned at the end of the string.


.. _styling-case-4-the-highlighted-string-is-not-contained-in-any-inline-element:

Case 4: The highlighted string is not contained in any inline element:
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

a) the option shown in the collapsed selectlist is 'No text style';

b) if the author chooses a class, the highlighted string is wrapped
with a 'span' element with the chosen class as value of its class
attribute;

c) the resulting marked up string is not highlighted;

d) the cursor is positioned at the end of the highlighted string.


.. _styling-case-5-the-highlighted-string-is-contained-in-an-inline-element:

Case 5: The highlighted string is contained in an inline element:
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

a) the option shown in the collapsed selectlist is 'No text style';

b) if the author chooses a class, the highlighted string is wrapped
with a 'span' element with the chosen class as value of its class
attribute;

c) the resulting marked up string is not highlighted;

d) the cursor is positioned at the end of the highlighted string.


.. _styling-case-6-the-hightlighted-string-contains-exactly-the-complete-inline-element:

Case 6: The hightlighted string contains exactly the complete inline element:
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Example of hightlighted string:<code>word</code>

a) the option shown in the collapsed selectlist is the current value
of the element's class attribute, or 'No style' when the inline
element bears no class attribute;

b) the selectlist is enabled and contains the classes allowed for the
type of inline element;

c) if the author chooses a different class, the class attribute gets
updated; the cursor is moved at the end of the highlighted string
which gets de-highlighted;

d)if the author chooses 'No style', the current class is removed; if
the element has no more class, the class attribute gets removed;if the
element is a span element and it has no more attribute, the span
element is removed; the cursor is moved at the end of the highlighted
string which gets de-highlighted.

