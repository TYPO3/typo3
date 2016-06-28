.. include:: ../../../Includes.txt


.. _reference-rules-between:

=======
between
=======

Checks if the submitted value is between the given minimum and maximum
value. By default, minimum and maximum are excluded, but can be included in
the validation.


.. _reference-rules-between-element:

element
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-element`.


.. _reference-rules-between-error:

error
=====

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-error`.

    For this specific rule the default error message consists of two parts,
    the second one will only be added when **inclusive** is set. This
    functionality is not possible when defining an own message as shown
    below.
    The markers %minimum and %maximum will be replaced with the values set
    by TypoScript.

:aspect:`Default:`
    *local language:*"The value is not between %minimum and %maximum(,
    inclusively)"


.. _reference-rules-between-inclusive:

inclusive
=========

:aspect:`Property:`
    inclusive

:aspect:`Data type:`
    boolean

:aspect:`Description:`
    If inclusive = 1, the minimum and maximum value are included in the
    comparison.

:aspect:`Default:`
    0


.. _reference-rules-between-message:

message
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-message`.

    For this specific rule the default message consists of two parts, the
    second one will only be added when **inclusive** is set. This
    functionality is not possible when defining an own message as shown
    below.
    The markers %minimum and %maximum will be replaced with the values set
    by TypoScript.

:aspect:`Default:`
    *local language:*"The value must be between %minimum and %maximum(,
    inclusively)"


.. _reference-rules-between-maximum:

maximum
=======

:aspect:`Property:`
    maximum

:aspect:`Data type:`
    integer

:aspect:`Description:`
    The maximum value of the comparison.


.. _reference-rules-between-minimum:

minimum
=======

:aspect:`Property:`
    minimum

:aspect:`Data type:`
    integer

:aspect:`Description:`
    The minimum value of the comparison.


.. _reference-rules-between-showmessage:

showMessage
===========

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-showmessage`.

[tsref:(cObject).FORM->rules.between]

