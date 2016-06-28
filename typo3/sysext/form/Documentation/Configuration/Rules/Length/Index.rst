.. include:: ../../../Includes.txt


.. _reference-rules-length:

======
length
======

Checks if the submitted value is of a certain length. A minimum length can
be used or a minimum and a maximum length.


.. _reference-rules-length-element:

element
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-element`.


.. _reference-rules-length-error:

error
=====

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-error`.

    For this specific rule the default error message consists of two parts,
    the second one will only be added when **maximum** is set. This
    functionality is not possible when defining an own message as shown
    below.
    The markers %minimum and %maximum will be replaced with the values set
    by TypoScript.

:aspect:`Default:`
    *local language:*"The value is less than %minimum characters long
    (, or longer than %maximum)"


.. _reference-rules-length-maximum:

maximum
=======

:aspect:`Property:`
    maximum

:aspect:`Data type:`
    integer

:aspect:`Description:`
    The maximum length of the submitted value. Maximum can only be used in
    combination with minimum.


.. _reference-rules-length-message:

message
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-message`.

    For this specific rule the default message consists of two parts, the
    second one will only be added when **maximum** is set. This
    functionality is not possible when defining an own message as shown
    below.
    The markers %minimum and %maximum will be replaced with the values set
    by TypoScript.

:aspect:`Default:`
    *local language:*"The length of the value must have a minimum of
    %minimum characters(, and a maximum of %maximum)"


.. _reference-rules-length-minimum:

minimum
=======

:aspect:`Property:`
    minimum

:aspect:`Data type:`
    integer

:aspect:`Description:`
    The minimum length of the submitted value.


.. _reference-rules-length-showmessage:

showMessage
===========

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-showmessage`.

[tsref:(cObject).FORM->rules.length]

