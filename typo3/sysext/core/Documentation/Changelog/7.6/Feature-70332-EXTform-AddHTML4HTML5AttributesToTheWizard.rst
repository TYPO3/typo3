
.. include:: /Includes.rst.txt

=======================================================================
Feature: #70332 - EXT:form - Add HTML4 / HTML5 attributes to the wizard
=======================================================================

See :issue:`70332`

Description
===========

The form wizard should support a wide set of attributes.
At the moment the wizard is able to set some attributes, but this
attribute-set is not complete and does not include HTML5 attributes.

The patch extends the wizard to set all universal HTML and HTML5
attributes based on selfhtml documentation version 8.1.2.

Currently supported attributes
------------------------------

`accept, acceptcharset, accesskey, action, alt, checked, class, cols,
dir, disabled, enctype, id, label, lang, maxlength, method, multiple,
name, placeholder, readonly, rows, selected, size, src, style, tabindex,
title, type, value`

New attributes
--------------

`autocomplete, autofocus, contenteditable, contextmenu, draggable,
dropzone, height, hidden, inputmode, list, max, min, minlength,
novalidate, pattern, required, selectionDirection, selectionEnd,
selectionStart, spellcheck, step, translate, width, wrap`

The **type attribute** will be extended with the following HTML5 types:

`color, date, datetime, datetime-local, email, month, number, range,
search, tel, time, url, week`

Each element is now able to set the HTML universal attributes and
element specific attributes.

Universal attributes are:

`accesskey, class, contenteditable, contextmenu, dir, draggable,
dropzone, hidden, id, lang, spellcheck, style, tabindex, title,
translate`

FORM
----

Currently supported attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

`accept, accept-charset, action, class, dir, enctype, id, lang, method,
style, title`

New attributes
^^^^^^^^^^^^^^

Universal attributes + `accept, accept-charset, action, autocomplete,
enctype, method, novalidate`

BUTTON
------

Currently supported attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

`accesskey, alt, class, dir, disabled, id, lang, name, style, tabindex,
title, type, value`

New attributes
^^^^^^^^^^^^^^

Universal attributes + `autofocus, disabled, name, type, value`

SELECT
------

Currently supported attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

`class, disabled, id, lang, multiple, name, size, style, tabindex,
title`

New attributes
^^^^^^^^^^^^^^

Universal attributes + `autofocus, disabled, multiple, name,
required, size`

TEXTAREA
--------

Currently supported attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

`accesskey, class, cols, dir, disabled, id, lang, name, placeholder,
readonly, rows, style, tabindex, title`

New attributes
^^^^^^^^^^^^^^

Universal attributes + `autofocus, cols, disabled, inputmode,
maxlength, minlength, name, placeholder, readonly, required, rows,
selectionDirection, selectionEnd, selectionStart, wrap`

SUBMIT
------

Currently supported attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

`accesskey, alt, class, dir, disabled, id, lang, name, style, tabindex,
title, type, value`

New attributes
^^^^^^^^^^^^^^

Universal attributes + `autofocus, disabled, name, type, value`

RADIO
-----

Currently supported attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

`accesskey, alt, class, dir, disabled, id, lang, name, style, tabindex,
title, type, value`

New attributes
^^^^^^^^^^^^^^

Universal attributes + `autofocus, checked, disabled, name, readonly,
required, type, value`

PASSWORD
--------

Currently supported attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

`accesskey, alt, class, dir, disabled, id, lang, maxlength, name,
placeholder, readonly, size, style, tabindex, title, type, value`

New attributes
^^^^^^^^^^^^^^

Universal attributes + `autocomplete, autofocus, disabled, maxlength,
minlength, name, pattern, placeholder, readonly, required, size, type,
value`

HIDDEN
------

Currently supported attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

`class, id, lang, name, style, type, value`

New attributes
^^^^^^^^^^^^^^

Universal attributes + `name, type, value`

FILEUPLOAD
----------

Currently supported attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

`accesskey, alt, class, dir, disabled, id, lang, name, size, style,
tabindex, title, type`

New attributes
^^^^^^^^^^^^^^

Universal attributes + `accept, autofocus, disabled, multiple name,
readonly, required, type, value`

RESET
-----

Currently supported attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

`accesskey, alt, class, dir, disabled, id, lang, name, style, tabindex,
title, type, value`

New attributes
^^^^^^^^^^^^^^

Universal attributes + `autofocus, disabled, name, type, value`

TEXTLINE
--------

Currently supported attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

`accesskey, alt, class, dir, disabled, id, lang, maxlength, name,
placeholder, readonly, size, style, tabindex, title, type, value`

New attributes
^^^^^^^^^^^^^^

Universal attributes + `autocomplete, autofocus, disabled, inputmode,
list, maxlength, minlength, name, pattern, placeholder, readonly,
required, size, type, value`

CHECKBOX

Currently supported attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

`accesskey, alt, class, dir, disabled, id, lang, name, style,
tabindex, title, type, value`

New attributes
^^^^^^^^^^^^^^

Universal attributes + `autofocus, checked, disabled, name, readonly,
required, type, value`


.. index:: Frontend, ext:form
