<?php
declare(strict_types = 1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Extbase\Reflection\PropertyInfo\Extractor;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\PropertyInfo\Util\PhpDocTypeHelper;
use TYPO3\CMS\Extbase\Reflection\DocBlock\Tags\Var_;

/**
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:extbase and not part of TYPO3's Core API.
 */
class PhpDocPropertyTypeExtractor implements PropertyTypeExtractorInterface
{
    /**
     * @var DocBlockFactory
     */
    private $docBlockFactory;

    /**
     * @var ContextFactory
     */
    private $contextFactory;

    /**
     * @var PhpDocTypeHelper
     */
    private $phpDocTypeHelper;

    /**
     * @var array
     */
    private static $reflectionContextCache = [];

    public function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
        $this->docBlockFactory->registerTagHandler('var', Var_::class);

        $this->contextFactory = new ContextFactory();
        $this->phpDocTypeHelper = new PhpDocTypeHelper();
    }

    /**
     * @param string $class
     * @param string $property
     * @param array $context
     *
     * @return Type[]|null
     */
    public function getTypes($class, $property, array $context = []): ?array
    {
        try {
            $reflectionProperty = $context['reflectionProperty'] ?? new \ReflectionProperty($class, $property);
        } catch (\ReflectionException $e) {
            return null;
        }

        if (!isset(static::$reflectionContextCache[$class])) {
            static::$reflectionContextCache[$class] = $this->contextFactory->createFromReflector(
                $reflectionProperty->getDeclaringClass()
            );
        }

        $docBlock = $this->getDocBlockFromProperty(
            $reflectionProperty,
            static::$reflectionContextCache[$class]
        );

        if ($docBlock === null) {
            return null;
        }

        $types = [];
        /** @var Var_ $tag */
        foreach ($docBlock->getTagsByName('var') as $tag) {
            if ($tag && null !== $tag->getType()) {
                $types = array_merge($types, $this->phpDocTypeHelper->getTypes($tag->getType()));
            }
        }

        if (!isset($types[0])) {
            return null;
        }

        return $types;
    }

    /**
     * @param ReflectionProperty $reflectionProperty
     * @param Context $context
     * @return DocBlock|null
     */
    private function getDocBlockFromProperty(ReflectionProperty $reflectionProperty, Context $context): ?DocBlock
    {
        try {
            return $this->docBlockFactory->create($reflectionProperty->getDocComment(), $context);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }
}
