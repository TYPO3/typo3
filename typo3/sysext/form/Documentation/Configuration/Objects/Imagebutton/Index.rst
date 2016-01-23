.. include:: ../../../Includes.txt


.. _reference-imagebutton:

===========
IMAGEBUTTON
===========

Creates a graphical submit button. The value of the src attribute specifies
the URI of the image that will decorate the button. For accessibility
reasons, authors should provide alternate text for the image via the alt
attribute.

When a pointing device is used to click on the image, the form is submitted
and the click coordinates passed to the server. The x value is measured in
pixels from the left of the image, and the y value in pixels from the top of
the image. The submitted data includes name.x=x-value and name.y=y-value
where "name" is the value of the name attribute, and x-value and y-value are
the x and y coordinate values, respectively.

If the server takes different actions depending on the location clicked,
users of non-graphical browsers will be disadvantaged. For this reason,
authors should consider alternate approaches:

- Use multiple submit buttons (each with its own image) in place of a
  single graphical submit button. Authors may use style sheets to control
  the positioning of these buttons.

- Use a client-side image map together with scripting.


.. _reference-imagebutton-accesskey:

accesskey
=========

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-accesskey`.


.. _reference-imagebutton-alt:

alt
===

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-alt`.


.. _reference-imagebutton-class:

class
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-class`.


.. _reference-imagebutton-dir:

dir
===

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-dir`.


.. _reference-imagebutton-disabled:

disabled
========

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-disabled`.


.. _reference-imagebutton-id:

id
==

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-id`.


.. _reference-imagebutton-label:

label
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-label`.


.. _reference-imagebutton-lang:

lang
====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-lang`.


.. _reference-imagebutton-layout:

layout
======

:aspect:`Description:`
    See general information for :ref:`reference-layout` and the :ref:`reference-layout-imagebutton`
    specific information.


.. _reference-imagebutton-name:

name
====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-name`.


.. _reference-imagebutton-src:

src
===

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-src`.


.. _reference-imagebutton-style:

style
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-style`.


.. _reference-imagebutton-tabindex:

tabindex
========

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-tabindex`.


.. _reference-imagebutton-title:

title
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-title`.


.. _reference-imagebutton-type:

type
====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-type`.

:aspect:`Default:`
    image


.. _reference-imagebutton-value:

value
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-value`.

[tsref:(cObject).FORM.FormObject.IMAGEBUTTON]

