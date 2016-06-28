.. include:: ../../../Includes.txt


.. _reference-rules-equals:

======
equals
======

Compares the submitted data of two FORM objects. If they are not equal, the
rule does not validate.

The rule and error messages will be put in the label of the object the rule
is attached with by the property "element".


.. _reference-rules-equals-element:

element
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-element`.


.. _reference-rules-equals-error:

error
=====

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-error`.

    The %field marker will be replaces with the property "field".

:aspect:`Default:`
    *local language:*"The value does not equal the value in field '%field'"


.. _reference-rules-equals-field:

field
=====

:aspect:`Property:`
    field

:aspect:`Data type:`
    string

:aspect:`Description:`
    The name of the object to compare with.

    See explanation of "element" property.


.. _reference-rules-equals-message:

message
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-message`.

    The %field marker will be replaces with the property "field".

:aspect:`Default:`
    *local language:*"This field must be equal to '%field'"


.. _reference-rules-equals-showmessage:

showMessage
===========

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-showmessage`.

[tsref:(cObject).FORM->rules.equals]

