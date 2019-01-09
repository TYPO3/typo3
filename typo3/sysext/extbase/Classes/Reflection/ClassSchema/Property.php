<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Extbase\Reflection\ClassSchema;

/**
 * Class TYPO3\CMS\Extbase\Reflection\ClassSchema\Property
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Property
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $definition;

    /**
     * @param string $name
     * @param array $definition
     */
    public function __construct(string $name, array $definition)
    {
        $this->name = $name;

        $defaults = [
            'type' => null,
            'elementType' => null,
            'public' => false,
            'protected' => false,
            'private' => false,
            'annotations' => [],
            'validators' => [],
        ];

        foreach ($defaults as $key => $defaultValue) {
            if (!isset($definition[$key])) {
                $definition[$key] = $defaultValue;
            }
        }

        $this->definition = $definition;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the type (string, integer, ...) set by the @var doc comment
     *
     * Returns null if type could not be evaluated
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->definition['type'];
    }

    /**
     * If the property is a collection of one of the types defined in
     * \TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::$collectionTypes,
     * the element type is evaluated and represents the type of collection
     * items inside the collection.
     *
     * Returns null if the property is not a collection and therefore no element type is defined.
     *
     * @return string|null
     */
    public function getElementType(): ?string
    {
        return $this->definition['elementType'];
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->definition['public'];
    }

    /**
     * @return bool
     */
    public function isProtected(): bool
    {
        return $this->definition['protected'];
    }

    /**
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->definition['private'];
    }

    /**
     * @param string $annotationKey
     * @return bool
     */
    public function hasAnnotation(string $annotationKey): bool
    {
        return isset($this->definition['annotations'][$annotationKey]);
    }

    /**
     * @param string $annotationKey
     * @return mixed
     */
    public function getAnnotationValue(string $annotationKey)
    {
        return $this->definition['annotations'][$annotationKey] ?? null;
    }

    /**
     * @return array
     */
    public function getValidators(): array
    {
        return $this->definition['validators'];
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->definition['defaultValue'];
    }
}
