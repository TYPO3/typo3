moveElementAfter()
''''''''''''''''''

Move FormElement $elementToMove after $referenceElement.
Both $elementToMove and $referenceElement must be direct descendants of this Section/Page.

Signature:

.. code-block:: php

    public function moveElementAfter(FormElementInterface $elementToMove, FormElementInterface $referenceElement);
