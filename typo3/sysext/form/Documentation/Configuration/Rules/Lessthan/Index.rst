.. include:: ../../../Includes.txt


.. _reference-rules-lessthan:

========
lessthan
========

Checks if the submitted value is less than the integer set in TypoScript.


.. _reference-rules-lessthan-element:

element
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-element`.


.. _reference-rules-lessthan-error:

error
=====

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-error`.

    The marker %maximum will be replaced with the value set in TypoScript.

:aspect:`Default:`
    *local language:*"The value does not appear to be less than %maximum"


.. _reference-rules-lessthan-maximum:

maximum
=======

:aspect:`Property:`
    maximum

:aspect:`Data type:`
    integer

:aspect:`Description:`
    The submitted value must be less than the maximum value.


.. _reference-rules-lessthan-message:

message
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-message`.

    The marker %maximum will be replaced with the value set in TypoScript.

:aspect:`Default:`
    *local language:*"The value must be less than %maximum"


.. _reference-rules-lessthan-showmessage:

showMessage
===========

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-showmessage`.

[tsref:(cObject).FORM->rules.lessthan]

