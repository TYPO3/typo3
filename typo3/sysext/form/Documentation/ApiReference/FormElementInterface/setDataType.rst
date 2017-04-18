setDataType()
'''''''''''''

The target data type the data should be converted through the property mapper.
 
Signature:

.. code-block:: php

    public function setDataType(string $dataType);

Example:

.. code-block:: php

    public function initializeFormElement()
    {
        $this->setDataType('TYPO3\CMS\Extbase\Domain\Model\FileReference');
        parent::initializeFormElement();
    }