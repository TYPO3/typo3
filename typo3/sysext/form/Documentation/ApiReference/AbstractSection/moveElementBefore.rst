moveElementBefore()
'''''''''''''''''''

Move FormElement $elementToMove before $referenceElement.
Both $elementToMove and $referenceElement must be direct descendants of this Section/Page.

Signature:

.. code-block:: php

    public function moveElementBefore(FormElementInterface $elementToMove, FormElementInterface $referenceElement);
