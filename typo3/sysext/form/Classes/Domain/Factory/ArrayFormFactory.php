<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Factory;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Exception\UnknownCompositRenderableException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractSection;
use TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface;

/**
 * A factory that creates a FormDefinition from an array
 *
 * Scope: frontend / backend
 */
class ArrayFormFactory extends AbstractFormFactory
{

    /**
     * Build a form definition, depending on some configuration.
     *
     * @param array $configuration
     * @param string $prototypeName
     * @return FormDefinition
     * @internal
     */
    public function build(array $configuration, string $prototypeName = null): FormDefinition
    {
        if (empty($prototypeName)) {
            $prototypeName = isset($configuration['prototypeName']) ? $configuration['prototypeName'] : 'standard';
        }
        $persistenceIdentifier = (isset($configuration['persistenceIdentifier'])) ? $configuration['persistenceIdentifier'] : null;

        $prototypeConfiguration = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(ConfigurationService::class)
            ->getPrototypeConfiguration($prototypeName);

        $form = GeneralUtility::makeInstance(ObjectManager::class)->get(
            FormDefinition::class,
            $configuration['identifier'],
            $prototypeConfiguration,
            'Form',
            $persistenceIdentifier
        );
        if (isset($configuration['renderables'])) {
            foreach ($configuration['renderables'] as $pageConfiguration) {
                $this->addNestedRenderable($pageConfiguration, $form);
            }
        }

        unset($configuration['persistenceIdentifier']);
        unset($configuration['prototypeName']);
        unset($configuration['renderables']);
        unset($configuration['type']);
        unset($configuration['identifier']);
        unset($configuration['label']);
        $form->setOptions($configuration);

        $this->triggerFormBuildingFinished($form);

        return $form;
    }

    /**
     * Add form elements to the $parentRenderable
     *
     * @param array $nestedRenderableConfiguration
     * @param CompositeRenderableInterface CompositeRenderableInterface $parentRenderable
     * @return mixed
     * @throws IdentifierNotValidException
     * @throws UnknownCompositRenderableException
     */
    protected function addNestedRenderable(array $nestedRenderableConfiguration, CompositeRenderableInterface $parentRenderable)
    {
        if (!isset($nestedRenderableConfiguration['identifier'])) {
            throw new IdentifierNotValidException('Identifier not set.', 1329289436);
        }
        if ($parentRenderable instanceof FormDefinition) {
            $renderable = $parentRenderable->createPage($nestedRenderableConfiguration['identifier'], $nestedRenderableConfiguration['type']);
        } elseif ($parentRenderable instanceof AbstractSection) {
            $renderable = $parentRenderable->createElement($nestedRenderableConfiguration['identifier'], $nestedRenderableConfiguration['type']);
        } else {
            throw new UnknownCompositRenderableException('Unknown composit renderable "' . get_class($parentRenderable) . '"', 1479593622);
        }

        if (isset($nestedRenderableConfiguration['renderables']) && is_array($nestedRenderableConfiguration['renderables'])) {
            $childRenderables = $nestedRenderableConfiguration['renderables'];
        } else {
            $childRenderables = [];
        }

        unset($nestedRenderableConfiguration['type']);
        unset($nestedRenderableConfiguration['identifier']);
        unset($nestedRenderableConfiguration['renderables']);

        $renderable->setOptions($nestedRenderableConfiguration);

        foreach ($childRenderables as $elementConfiguration) {
            $this->addNestedRenderable($elementConfiguration, $renderable);
        }

        return $renderable;
    }
}
