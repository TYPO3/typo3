.. include:: ../../../Includes.txt


.. _reference-rules-float:

=====
float
=====

Checks if the submitted value is a floating point number (aka floats,
doubles or real numbers).

Float depends on your config.locale\_all setting. For German
(config.locale\_all = de\_DE) one will get the following values (partly)
with the PHP function localeconv():

- 'decimal\_point' => string '.' Decimal point character
- 'thousands\_sep' => string '' Thousands separator
- 'mon\_decimal\_point' => string ',' Monetary decimal point character
- 'mon\_thousands\_sep' => string '.' Monetary thousands separator

First both thousands separators are deleted from the float, then the decimal
points are replaced by a dot to get a proper float which PHP can handle
properly.


.. _reference-rules-float-element:

element
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-element`.


.. _reference-rules-float-error:

error
=====

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-error`.

:aspect:`Default:`
    *local language:*"The value does not appear to be a float"


.. _reference-rules-float-message:

message
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-message`.

:aspect:`Default:`
    *local language:*"Enter a float"


.. _reference-rules-float-showmessage:

showMessage
===========

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-showmessage`.

[tsref:(cObject).FORM->rules.float]

