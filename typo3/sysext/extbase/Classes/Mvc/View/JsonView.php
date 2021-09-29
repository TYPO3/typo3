<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Mvc\View;

use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3Fluid\Fluid\View\AbstractView;

/**
 * A JSON view
 *
 * @todo v12: Drop 'implements ViewInterface' together with removal of extbase ViewInterface
 */
class JsonView extends AbstractView implements ViewInterface
{
    /**
     * Definition for the class name exposure configuration,
     * that is, if the class name of an object should also be
     * part of the output JSON, if configured.
     *
     * Setting this value, the object's class name is fully
     * put out, including the namespace.
     */
    const EXPOSE_CLASSNAME_FULLY_QUALIFIED = 1;

    /**
     * Puts out only the actual class name without namespace.
     * See EXPOSE_CLASSNAME_FULL for the meaning of the constant at all.
     */
    const EXPOSE_CLASSNAME_UNQUALIFIED = 2;

    /**
     * Only variables whose name is contained in this array will be rendered
     *
     * @var string[]
     */
    protected $variablesToRender = ['value'];

    /**
     * @var string
     */
    protected $currentVariable = '';

    /**
     * The rendering configuration for this JSON view which
     * determines which properties of each variable to render.
     *
     * The configuration array must have the following structure:
     *
     * Example 1:
     *
     * [
     *      'variable1' => [
     *          '_only' => ['property1', 'property2', ...]
     *      ],
     *      'variable2' => [
     *          '_exclude' => ['property3', 'property4, ...]
     *      ],
     *      'variable3' => [
     *          '_exclude' => ['secretTitle'],
     *          '_descend' => [
     *              'customer' => [
     *                  '_only' => ['firstName', 'lastName']
     *              ]
     *          ]
     *      ],
     *      'somearrayvalue' => [
     *          '_descendAll' => [
     *              '_only' => ['property1']
     *          ]
     *      ]
     * ]
     *
     * Of variable1 only property1 and property2 will be included.
     * Of variable2 all properties except property3 and property4
     * are used.
     * Of variable3 all properties except secretTitle are included.
     *
     * If a property value is an array or object, it is not included
     * by default. If, however, such a property is listed in a "_descend"
     * section, the renderer will descend into this sub structure and
     * include all its properties (of the next level).
     *
     * The configuration of each property in "_descend" has the same syntax
     * like at the top level. Therefore - theoretically - infinitely nested
     * structures can be configured.
     *
     * To export indexed arrays the "_descendAll" section can be used to
     * include all array keys for the output. The configuration inside a
     * "_descendAll" will be applied to each array element.
     *
     *
     * Example 2: exposing object identifier
     *
     * [
     *      'variableFoo' => [
     *          '_exclude' => ['secretTitle'],
     *          '_descend' => [
     *              'customer' => [    // consider 'customer' being a persisted entity
     *                  '_only' => ['firstName'],
     *                  '_exposeObjectIdentifier' => TRUE,
     *                  '_exposedObjectIdentifierKey' => 'guid'
     *              ]
     *          ]
     *      ]
     * ]
     *
     * Note for entity objects you are able to expose the object's identifier
     * also, just add an "_exposeObjectIdentifier" directive set to TRUE and
     * an additional property '__identity' will appear keeping the persistence
     * identifier. Renaming that property name instead of '__identity' is also
     * possible with the directive "_exposedObjectIdentifierKey".
     * Example 2 above would output (summarized):
     * {"customer":{"firstName":"John","guid":"892693e4-b570-46fe-af71-1ad32918fb64"}}
     *
     *
     * Example 3: exposing object's class name
     *
     * [
     *      'variableFoo' => [
     *          '_exclude' => ['secretTitle'],
     *          '_descend' => [
     *              'customer' => [    // consider 'customer' being an object
     *                  '_only' => ['firstName'],
     *                  '_exposeClassName' => \TYPO3\CMS\Extbase\Mvc\View\JsonView::EXPOSE_CLASSNAME_FULLY_QUALIFIED
     *              ]
     *          ]
     *      ]
     * ]
     *
     * The ``_exposeClassName`` is similar to the objectIdentifier one, but the class name is added to the
     * JSON object output, for example (summarized):
     * {"customer":{"firstName":"John","__class":"Acme\Foo\Domain\Model\Customer"}}
     *
     * The other option is EXPOSE_CLASSNAME_UNQUALIFIED which only will give the last part of the class
     * without the namespace, for example (summarized):
     * {"customer":{"firstName":"John","__class":"Customer"}}
     * This might be of interest to not provide information about the package or domain structure behind.
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var ControllerContext
     * @deprecated since v11, will be removed with v12.
     */
    protected $controllerContext;

    /**
     * View variables and their values
     *
     * @var array
     * @see assign()
     */
    protected $variables = [];

    /**
     * @param PersistenceManagerInterface $persistenceManager
     * @internal
     */
    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Sets the current controller context
     *
     * @param ControllerContext $controllerContext
     * @deprecated since v11, will be removed with v12.
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }

    /**
     * Add a variable to $this->viewData.
     * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
     *
     * @param string $key Key of variable
     * @param mixed $value Value of object
     * @return self an instance of $this, to enable chaining
     */
    public function assign($key, $value)
    {
        $this->variables[$key] = $value;
        return $this;
    }

    /**
     * Add multiple variables to $this->viewData.
     *
     * @param array $values array in the format array(key1 => value1, key2 => value2).
     * @return self an instance of $this, to enable chaining
     */
    public function assignMultiple(array $values)
    {
        foreach ($values as $key => $value) {
            $this->assign($key, $value);
        }
        return $this;
    }

    /**
     * Tells if the view implementation can render the view for the given context.
     *
     * By default we assume that the view implementation can handle all kinds of
     * contexts. Override this method if that is not the case.
     *
     * @return bool TRUE if the view has something useful to display, otherwise FALSE
     * @deprecated since TYPO3 v11, will be removed in v12. Legacy method, not part of ViewInterface anymore.
     */
    public function canRender()
    {
        trigger_error('Method ' . __METHOD__ . ' has been deprecated in v11 and will be removed with v12.', E_USER_DEPRECATED);
        return true;
    }

    /**
     * Initializes this view.
     *
     * Override this method for initializing your concrete view implementation.
     * @deprecated since v11, will be removed with v12. Drop together with removal of extbase ViewInterface.
     */
    public function initializeView()
    {
    }

    /**
     * Specifies which variables this JsonView should render
     * By default only the variable 'value' will be rendered
     *
     * @param array $variablesToRender
     */
    public function setVariablesToRender(array $variablesToRender): void
    {
        $this->variablesToRender = $variablesToRender;
    }

    /**
     * @param array $configuration The rendering configuration for this JSON view
     */
    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * @inheritdoc
     */
    public function renderSection($sectionName, array $variables = [], $ignoreUnknown = false)
    {
        // No-op: renderSection does not make sense for this view
        return '';
    }

    /**
     * @inheritdoc
     */
    public function renderPartial($partialName, $sectionName, array $variables, $ignoreUnknown = false)
    {
        // No-op: renderPartial does not make sense for this view
        return '';
    }

    /**
     * Transforms the value view variable to a serializable
     * array representation using a YAML view configuration and JSON encodes
     * the result.
     *
     * @return string The JSON encoded variables
     */
    public function render(): string
    {
        $propertiesToRender = $this->renderArray();
        return json_encode($propertiesToRender, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Loads the configuration and transforms the value to a serializable
     * array.
     *
     * @return mixed
     */
    protected function renderArray()
    {
        if (count($this->variablesToRender) === 1) {
            $firstLevel = false;
            $variableName = current($this->variablesToRender);
            $this->currentVariable = $variableName;
            $valueToRender = $this->variables[$variableName] ?? null;
            $configuration = $this->configuration[$variableName] ?? [];
        } else {
            $firstLevel = true;
            $valueToRender = [];
            foreach ($this->variablesToRender as $variableName) {
                $valueToRender[$variableName] = $this->variables[$variableName] ?? null;
            }
            $configuration = $this->configuration;
        }
        return $this->transformValue($valueToRender, $configuration, $firstLevel);
    }

    /**
     * Transforms a value depending on type recursively using the
     * supplied configuration.
     *
     * @param mixed $value The value to transform
     * @param array $configuration Configuration for transforming the value
     * @param bool $firstLevel
     * @return mixed The transformed value
     */
    protected function transformValue($value, array $configuration, $firstLevel = false)
    {
        if (is_array($value) || $value instanceof \ArrayAccess) {
            $array = [];
            foreach ($value as $key => $element) {
                if ($firstLevel) {
                    $this->currentVariable = $key;
                }
                if (isset($configuration['_descendAll']) && is_array($configuration['_descendAll'])) {
                    $array[$key] = $this->transformValue($element, $configuration['_descendAll']);
                } else {
                    if (isset($configuration['_only']) && is_array($configuration['_only']) && !in_array($key, $configuration['_only'], true)) {
                        continue;
                    }
                    if (isset($configuration['_exclude']) && is_array($configuration['_exclude']) && in_array($key, $configuration['_exclude'], true)) {
                        continue;
                    }
                    $array[$key] = $this->transformValue($element, $configuration[$key] ?? []);
                }
            }
            return $array;
        }
        if (is_object($value)) {
            return $this->transformObject($value, $configuration);
        }
        return $value;
    }

    /**
     * Traverses the given object structure in order to transform it into an
     * array structure.
     *
     * @param object $object Object to traverse
     * @param array $configuration Configuration for transforming the given object or NULL
     * @return array|string Object structure as an array or as a rendered string (for a DateTime instance)
     */
    protected function transformObject(object $object, array $configuration)
    {
        if ($object instanceof \DateTimeInterface) {
            return $object->format(\DateTimeInterface::ATOM);
        }
        $propertyNames = ObjectAccess::getGettablePropertyNames($object);

        $propertiesToRender = [];
        foreach ($propertyNames as $propertyName) {
            if (isset($configuration['_only']) && is_array($configuration['_only']) && !in_array($propertyName, $configuration['_only'], true)) {
                continue;
            }
            if (isset($configuration['_exclude']) && is_array($configuration['_exclude']) && in_array($propertyName, $configuration['_exclude'], true)) {
                continue;
            }

            $propertyValue = ObjectAccess::getProperty($object, $propertyName);

            if (!is_array($propertyValue) && !is_object($propertyValue)) {
                $propertiesToRender[$propertyName] = $propertyValue;
            } elseif (isset($configuration['_descend']) && array_key_exists($propertyName, $configuration['_descend'])) {
                $propertiesToRender[$propertyName] = $this->transformValue($propertyValue, $configuration['_descend'][$propertyName]);
            } elseif (isset($configuration['_recursive']) && in_array($propertyName, $configuration['_recursive'])) {
                $propertiesToRender[$propertyName] = $this->transformValue($propertyValue, $this->configuration[$this->currentVariable]);
            }
        }
        if (isset($configuration['_exposeObjectIdentifier']) && $configuration['_exposeObjectIdentifier'] === true) {
            if (isset($configuration['_exposedObjectIdentifierKey']) && strlen($configuration['_exposedObjectIdentifierKey']) > 0) {
                $identityKey = $configuration['_exposedObjectIdentifierKey'];
            } else {
                $identityKey = '__identity';
            }
            $propertiesToRender[$identityKey] = $this->persistenceManager->getIdentifierByObject($object);
        }
        if (isset($configuration['_exposeClassName']) && ($configuration['_exposeClassName'] === self::EXPOSE_CLASSNAME_FULLY_QUALIFIED || $configuration['_exposeClassName'] === self::EXPOSE_CLASSNAME_UNQUALIFIED)) {
            $className = get_class($object);
            $classNameParts = explode('\\', $className);
            $propertiesToRender['__class'] = ($configuration['_exposeClassName'] === self::EXPOSE_CLASSNAME_FULLY_QUALIFIED ? $className : array_pop($classNameParts));
        }

        return $propertiesToRender;
    }
}
