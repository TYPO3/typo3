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

namespace TYPO3\CMS\Extbase\Reflection\DocBlock\Tags;

use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Context as TypeContext;
use Webmozart\Assert\Assert;

/**
 * Class TYPO3\CMS\Extbase\Reflection\DocBlock\Tags\Var_
 */
class Var_ extends \phpDocumentor\Reflection\DocBlock\Tags\Var_
{
    /**
     * @param $body
     * @param TypeResolver|null $typeResolver
     * @param DescriptionFactory|null $descriptionFactory
     * @param TypeContext|null $context
     * @return \phpDocumentor\Reflection\DocBlock\Tags\Var_|Var_
     */
    public static function create(
        $body,
        TypeResolver $typeResolver = null,
        DescriptionFactory $descriptionFactory = null,
        TypeContext $context = null
    ) {
        /*
         * This class is needed to detect collections like
         * @var Collection<CollectionType>
         *
         * While writing this comment, TYPO3 has a dependency to
         * phpdocumentor/type-resolver:^0.4.0 via
         * phpdocumentor/reflection-docblock:4.3.0.
         *
         * phpdocumentor/type-resolver can detect collections from
         * version 0.5.0 on, but as there is no newer version of
         * phpdocumentor/reflection-docblock, this feature is
         * unavailable at the moment.
         *
         * Once phpdocumentor/reflection-docblock:5.0.0 has been
         * released, TYPO3 should use that version along with an
         * updated version of phpdocumentor/type-resolver and
         * this class should be removed then.
         */
        Assert::stringNotEmpty($body);
        Assert::allNotNull([$typeResolver, $descriptionFactory]);

        $parts        = preg_split('/(\s+)/Su', $body, 3, PREG_SPLIT_DELIM_CAPTURE);
        $type         = null;
        $variableName = '';

        // if the first item that is encountered is not a variable; it is a type
        if (isset($parts[0]) && (strlen($parts[0]) > 0) && ($parts[0][0] !== '$')) {
            $currentPart = array_shift($parts);

            $matches = [];
            $pattern = '/(?P<type>[^\s<>]+)<(?P<elementType>[^\s<>]+)>/';
            if (preg_match($pattern, $currentPart, $matches)) {
                $type = new Compound([
                    $typeResolver->resolve($matches['type'], $context),
                    $typeResolver->resolve($matches['elementType'] . '[]', $context),
                ]);
            } else {
                $type = $typeResolver->resolve($currentPart, $context);
            }

            array_shift($parts);
        }

        // if the next item starts with a $ or ...$ it must be the variable name
        if (isset($parts[0]) && (strlen($parts[0]) > 0) && ($parts[0][0] === '$')) {
            $variableName = array_shift($parts);
            array_shift($parts);

            if (substr($variableName, 0, 1) === '$') {
                $variableName = substr($variableName, 1);
            }
        }

        $description = $descriptionFactory->create(implode('', $parts), $context);

        return new static($variableName, $type, $description);
    }
}
