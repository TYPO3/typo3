.. include:: ../../../Includes.txt


.. _reference-radio:

=====
RADIO
=====

Creates a radio button.

Radio buttons are on/ off switches that may be toggled by the user. A switch
is "on" when the control element's checked attribute is set. When a form is
submitted, only "on" radio button controls can become successful.

Several radio buttons in a form may share the same control name. Thus, for
example, radio buttons allow users to select several values for the same
property.

Radio buttons are like checkboxes except that when several share the same
control name, they are mutually exclusive: when one is switched "on", all
others with the same name are switched "off".

Radio buttons are normally grouped in a FIELDSET object.

**Note from W3C for user agent behaviour**: If no radio button in a set
sharing the same control name is initially "on", user agent behavior for
choosing which control is initially "on" is undefined.

**Note**: Since existing implementations handle this case differently, the
current specification differs from RFC 1866 ([RFC1866] section 8.1.2.4),
which states:

At all times, exactly one of the radio buttons in a set is checked. If
none of the elements of a set of radio buttons specifies \`checked',
then the user agent must check the first radio button of the set initially.

Since user agent behavior differs, authors should ensure that in each set of
radio buttons that one is initially "on".


.. _reference-radio-accesskey:

accesskey
=========

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-accesskey`.


.. _reference-radio-alt:

alt
===

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-alt`.


.. _reference-radio-checked:

checked
=======

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-checked`.


.. _reference-radio-class:

class
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-class`.


.. _reference-radio-dir:

dir
===

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-dir`.


.. _reference-radio-disabled:

disabled
========

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-disabled`.


.. _reference-radio-id:

id
==

:aspect:`Description:`
    See general information for :ref:`reference-objects-attributes-id`.


.. _reference-radio-label:

label
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-label`.


.. _reference-radio-lang:

lang
====

:aspect:`Description:`
    See general information for :ref:`reference-objects-attributes-lang`.


.. _reference-radio-layout:

layout
======

:aspect:`Description:`
    See general information for :ref:`reference-layout` and the :ref:`reference-layout-radio`
    specific information.


.. _reference-radio-name:

name
====

:aspect:`Description:`
    See general information for :ref:`reference-objects-attributes-name`.


.. _reference-radio-style:

style
=====

:aspect:`Description:`
    See general information for :ref:`reference-objects-attributes-style`.


.. _reference-radio-tabindex:

tabindex
========

:aspect:`Description:`
    See general information for :ref:`reference-objects-attributes-tabindex`.


.. _reference-radio-title:

title
=====

:aspect:`Description:`
    See general information for :ref:`reference-objects-attributes-title`.


.. _reference-radio-type:

type
====

:aspect:`Description:`
    See general information for :ref:`reference-objects-attributes-type`.

:aspect:`Default:`
    radio


.. _reference-radio-value:

value
=====

:aspect:`Description:`
    See general information for :ref:`reference-objects-attributes-value`.

[tsref:(cObject).FORM.FormObject.RADIO]

