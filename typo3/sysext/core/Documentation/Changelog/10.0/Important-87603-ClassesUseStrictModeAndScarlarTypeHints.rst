.. include:: /Includes.rst.txt

=================================================================
Important: #87594 - Classes use strict mode and scalar type hints
=================================================================

See :issue:`87594`

Description
===========

The following PHP classes now use strict mode
and their methods will force parameter types with scalar type hints:

- :php:`\TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager`
- :php:`\TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager`
- :php:`\TYPO3\CMS\Extbase\Configuration\ConfigurationManager`
- :php:`\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface`
- :php:`\TYPO3\CMS\Extbase\Configuration\Exception`
- :php:`\TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException`
- :php:`\TYPO3\CMS\Extbase\Configuration\Exception\ParseErrorException`
- :php:`\TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager`
- :php:`\TYPO3\CMS\Extbase\Core\Bootstrap`
- :php:`\TYPO3\CMS\Extbase\Core\BootstrapInterface`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\BackendUserGroupRepository`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\FileMountRepository`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository`
- :php:`\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject`
- :php:`\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface`
- :php:`\TYPO3\CMS\Extbase\Error\Error`
- :php:`\TYPO3\CMS\Extbase\Error\Message`
- :php:`\TYPO3\CMS\Extbase\Error\Notice`
- :php:`\TYPO3\CMS\Extbase\Error\Result`
- :php:`\TYPO3\CMS\Extbase\Error\Warning`
- :php:`\TYPO3\CMS\Extbase\Exception`
- :php:`\TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\InfiniteLoopException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentMixingException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentValueException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\InvalidRequestMethodException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\NoSuchActionException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\NoSuchControllerException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder`
- :php:`\TYPO3\CMS\Extbase\Object\Container\Container`
- :php:`\TYPO3\CMS\Extbase\Object\Container\Exception\UnknownObjectException`
- :php:`\TYPO3\CMS\Extbase\Object\Exception`
- :php:`\TYPO3\CMS\Extbase\Object\Exception\CannotBuildObjectException`
- :php:`\TYPO3\CMS\Extbase\Object\Exception\CannotReconstituteObjectException`
- :php:`\TYPO3\CMS\Extbase\Object\ObjectManager`
- :php:`\TYPO3\CMS\Extbase\Object\ObjectManagerInterface`
- :php:`\TYPO3\CMS\Extbase\Persistence\Exception`
- :php:`\TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Exception\IllegalRelationTypeException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Exception`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Exception\InconsistentQuerySettingsException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidClassException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidNumberOfConstraintsException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidRelationConfigurationException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Exception\MissingColumnMapException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Exception\TooDirtyException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedOrderException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedRelationException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\BadConstraintException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend`
- :php:`\TYPO3\CMS\Extbase\Property\Exception`
- :php:`\TYPO3\CMS\Extbase\Property\Exception\DuplicateObjectException`
- :php:`\TYPO3\CMS\Extbase\Property\Exception\DuplicateTypeConverterException`
- :php:`\TYPO3\CMS\Extbase\Property\Exception\InvalidDataTypeException`
- :php:`\TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException`
- :php:`\TYPO3\CMS\Extbase\Property\Exception\InvalidSourceException`
- :php:`\TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException`
- :php:`\TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException`
- :php:`\TYPO3\CMS\Extbase\Property\Exception\TypeConverterException`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\AbstractFileCollectionConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\AbstractFileFolderConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\ArrayConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\BooleanConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\CoreTypeConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\FileConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\FileReferenceConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\FloatConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\FolderBasedFileCollectionConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\FolderConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\IntegerConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\ObjectStorageConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\StaticFileCollectionConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\StringConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverterInterface`
- :php:`\TYPO3\CMS\Extbase\Reflection\ClassSchema`
- :php:`\TYPO3\CMS\Extbase\Reflection\Exception`
- :php:`\TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException`
- :php:`\TYPO3\CMS\Extbase\Reflection\Exception\UnknownClassException`
- :php:`\TYPO3\CMS\Extbase\Reflection\ObjectAccess`
- :php:`\TYPO3\CMS\Extbase\Security\Cryptography\HashService`
- :php:`\TYPO3\CMS\Extbase\Security\Exception`
- :php:`\TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException`
- :php:`\TYPO3\CMS\Extbase\Security\Exception\InvalidHashException`
- :php:`\TYPO3\CMS\Extbase\Service\CacheService`
- :php:`\TYPO3\CMS\Extbase\Service\EnvironmentService`
- :php:`\TYPO3\CMS\Extbase\Service\ExtensionService`
- :php:`\TYPO3\CMS\Extbase\Service\ImageService`
- :php:`\TYPO3\CMS\Extbase\SignalSlot\Dispatcher`
- :php:`\TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException`
- :php:`\TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException`
- :php:`\TYPO3\CMS\Extbase\Utility\DebuggerUtility`
- :php:`\TYPO3\CMS\Extbase\Utility\Exception\InvalidTypeException`
- :php:`\TYPO3\CMS\Extbase\Utility\FrontendSimulatorUtility`
- :php:`\TYPO3\CMS\Extbase\Utility\LocalizationUtility`
- :php:`\TYPO3\CMS\Extbase\Utility\TypeHandlingUtility`
- :php:`\TYPO3\CMS\Extbase\Validation\Exception`
- :php:`\TYPO3\CMS\Extbase\Validation\Exception\InvalidTypeHintException`
- :php:`\TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException`
- :php:`\TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException`
- :php:`\TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException`

.. index:: Backend, PHP-API, ext:extbase
