.. include:: ../../../Includes.txt


.. _reference-rules-greaterthan:

===========
greaterthan
===========

Checks if the submitted value is greater than the integer set in TypoScript.


.. _reference-rules-greaterthan-element:

element
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-element`.


.. _reference-rules-greaterthan-error:

error
=====

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-error`.

    The marker %minimum will be replaced with the value set in TypoScript.

:aspect:`Default:`
    *local language:*"The value does not appear to be greater than %minimum"


.. _reference-rules-greaterthan-message:

message
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-message`.

    The marker %minimum will be replaced with the value set by TypoScript.

:aspect:`Default:`
    *local language:*"The value must be greater than %minimum"


.. _reference-rules-greaterthan-minimum:

minimum
=======

:aspect:`Property:`
    minimum

:aspect:`Data type:`
    integer

:aspect:`Description:`
    The submitted value must be greater than the minimum value.


.. _reference-rules-greaterthan-showmessage:

showMessage
===========

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-showmessage`.

[tsref:(cObject).FORM->rules.greaterthan]

