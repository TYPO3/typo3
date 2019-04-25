setDataType()
'''''''''''''

The target data type the data should be converted through the property mapper.

Signature::

   public function setDataType(string $dataType);

Example::

   public function initializeFormElement()
   {
       $this->setDataType('TYPO3\CMS\Extbase\Domain\Model\FileReference');
       parent::initializeFormElement();
   }
