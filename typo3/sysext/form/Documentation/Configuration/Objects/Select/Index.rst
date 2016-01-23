.. include:: ../../../Includes.txt


.. _reference-select:

======
SELECT
======

The SELECT object creates a menu. Each choice offered by the menu is
represented by an OPTION object. A SELECT object must contain at least one
OPTION object

**Pre-selected options**

Zero or more choices may be pre-selected for the user. User agents should
determine which choices are pre-selected as follows:

- If no OPTION object has the selected attribute set, user agent behavior
  for choosing which option is initially selected is undefined.
  **Note**: Since existing implementations handle this case differently, the
  current specification differs from RFC 1866 ([RFC1866] section 8.1.3),
  which states: The initial state has the first option selected, unless a
  SELECTED attribute is present on any of the <OPTION> elements. Since user
  agent behavior differs, one should ensure that each menu includes a
  default pre-selected OPTION.

- If one OPTION object has the selected attribute set, it should be pre-
  selected.

- If the SELECT object has the multiple attribute set and more than one
  OPTION object has the selected attribute set, they should all be pre-
  selected.

- It is considered an error if more than one OPTION object has the selected
  attribute set and the SELECT object does not have the multiple attribute
  set. User agents may vary in how they handle this error, but should not
  pre-select more than one choice.


.. _reference-select-1-2-3-4:

1, 2, 3, 4 ...
==============

:aspect:`Property:`
    1, 2, 3, 4 ...

:aspect:`Data type:`
    [array of FORM objects]

:aspect:`Description:`
    OPTION and/ or OPTGROUP objects, part of the SELECT.


.. _reference-select-class:

class
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-class`.


.. _reference-select-disabled:

disabled
========

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-disabled`.


.. _reference-select-id:

id
==

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-id`.


.. _reference-select-label:

label
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-label`.


.. _reference-select-lang:

lang
====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-lang`.


.. _reference-select-layout:

layout
======

:aspect:`Description:`
    See general information for :ref:`reference-layout` and the :ref:`reference-layout-select`
    specific information.


.. _reference-select-multiple:

multiple
========

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-multiple`.


.. _reference-select-name:

name
====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-name`.


.. _reference-select-size:

size
====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-size`.


.. _reference-select-style:

style
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-style`.


.. _reference-select-tabindex:

tabindex
========

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-tabindex`.


.. _reference-select-title:

title
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-title`.

[tsref:(cObject).FORM.FormObject.SELECT]

