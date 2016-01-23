.. include:: ../../../Includes.txt


.. _reference-filters-currency:

========
currency
========

Changes a number to a formatted version with two decimals. The decimals
point and thousands separator are configurable.

**Example**

- Submitted data: 100000.99

- Filtered: 100 000,99

.. code-block:: typoscript

  filters {
    1 = currency
    1 {
      decimalPoint = ,
      thousandSeparator = space
    }
  }


.. _reference-filters-currency-decimalpoint:

decimalPoint
============

:aspect:`Property:`
    decimalPoint

:aspect:`Data type:`
    string

:aspect:`Description:`
    Value for the decimal point, mostly a dot '.' or a comma ','

:aspect:`Default:`
    .


.. _reference-filters-currency-thousandseparator:

thousandSeparator
=================

:aspect:`Property:`
    thousandSeparator

:aspect:`Data type:`
    string

:aspect:`Description:`
    Value for the thousand separator.

    Special values:

    - **space** : Adds a space as thousand separator
    - **none** : No thousand separator

:aspect:`Default:`
    ,

[tsref:(cObject).FORM->filters.currency]

