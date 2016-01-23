.. include:: ../../../Includes.txt


.. _reference-form:

====
FORM
====

A form will always start with the FORM object. TYPO3 recognizes this object
and sends all TypoScript data to the FORM extension.


.. _reference-form-accept:

accept
======

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-accept`.


.. _reference-form-accept-charset:

accept-charset
==============

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-accept-charset`.


.. _reference-form-action:

action
======

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-action`.


.. _reference-form-class:

class
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-class`.


.. _reference-form-dir:

dir
===

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-dir`.


.. _reference-form-enctype:

enctype
=======

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-enctype`.


.. _reference-form-id:

id
==

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-id`.


.. _reference-form-lang:

lang
====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-lang`.


.. _reference-form-layout:

layout
======

:aspect:`Description:`
    See general information for :ref:`reference-layout` and the :ref:`reference-layout-form`
    specific information.


.. _reference-form-method:

method
======

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-method`.


.. _reference-form-name:

name
====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-name`.


.. _reference-form-postprocessor:

postProcessor
=============

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-postProcessor`.


.. _reference-form-prefix:

prefix
======

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-prefix`.


.. _reference-form-rules:

rules
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-rules`.


.. _reference-form-style:

style
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-style`.


.. _reference-form-title:

title
=====

:aspect:`Description:`
    See general information for  :ref:`reference-objects-attributes-title`.


[tsref:(cObject).FORM]

.. _reference-form-example:

Example
=======

This example shows a simple payment form. At the beginning the layout of the
radio buttons is changed for the form view. The label and the input field
are switched.

The example builds a form as TypoScript library which can be assigned to a
marker or used inside a fluid template. The defined layout settings are only
valid within this TS library.

.. code-block:: typoscript

  lib.form = FORM
  lib.form {
    method = post

    postProcessor {
      # ...
    }

    form {
      layout {
        radio (
          <input />
          <label />
        )
      }
    }

    10 = FIELDSET
    10 {
      legend = Name

      10 = SELECT
      10 {
        label = Title

        10 = OPTION
        10 {
          data = Mr.
          selected = 1
        }

        20 = OPTION
        20 {
          data = Mrs.
        }

        30 = OPTION
        30 {
          data = Ms.
        }

        40 = OPTION
        40 {
          data = Dr.
        }

        50 = OPTION
        50 {
          data = Viscount
        }
      }

      20 = TEXTLINE
      20 {
        label = First name
      }

      30 = TEXTLINE
      30 {
        label = Last name
      }
    }

    20 = FIELDSET
    20 {
      legend = Address

      10 = TEXTLINE
      10 {
        label = Street
      }

      20 = TEXTLINE
      20 {
        label = City
      }

      30 = TEXTLINE
      30 {
        label = State
      }

      40 = TEXTLINE
      40 {
        label = ZIP code
      }
    }

    30 = FIELDSET
    30 {
      legend = Payment details

      10 = FIELDSET
      10 {
        legend = Credit card

        10 = RADIO
        10 {
          label = American Express
          name = creditcard
        }

        20 = RADIO
        20 {
          label = Mastercard
          name = creditcard
        }

        30 = RADIO
        30 {
          label = Visa
          name = creditcard
        }

        40 = RADIO
        40 {
          label = Blockbuster Card
          name = creditcard
        }
      }

      20 = TEXTLINE
      20 {
        label = Card number
      }

      30 = TEXTLINE
      30 {
        label = Expiry date
      }
    }

    40 = SUBMIT
    40 {
      value = Submit my details
    }
  }

