<?php
namespace TYPO3\CMS\Form\PostProcess;

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

use TYPO3\CMS\Core\TypoScript\TemplateService;

/**
 * The post processor
 */
class PostProcessor extends AbstractPostProcessor
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $postProcessorTypoScript;

    /**
     * @var \TYPO3\CMS\Form\Domain\Model\Element $form
     */
    protected $form;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     * @return void
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Constructor
     *
     * @param \TYPO3\CMS\Form\Domain\Model\Element $form
     * @param array $postProcessorTypoScript Post processor TypoScript settings
     */
    public function __construct(\TYPO3\CMS\Form\Domain\Model\Element $form, array $postProcessorTypoScript)
    {
        $this->form = $form;
        $this->postProcessorTypoScript = $postProcessorTypoScript;
    }

    /**
     * The main method called by the controller
     *
     * Iterates over the configured post processors and calls them with their
     * own settings
     *
     * @return string HTML messages from the called processors
     */
    public function process()
    {
        $html = '';

        if (is_array($this->postProcessorTypoScript)) {
            $keys = TemplateService::sortedKeyList($this->postProcessorTypoScript);

            foreach ($keys as $key) {
                if (!(int)$key || strpos($key, '.') !== false) {
                    continue;
                }
                $className = false;
                $processorName = $this->postProcessorTypoScript[$key];
                $processorArguments = [];
                if (isset($this->postProcessorTypoScript[$key . '.'])) {
                    $processorArguments = $this->postProcessorTypoScript[$key . '.'];
                }

                if (class_exists($processorName, true)) {
                    $className = $processorName;
                } else {
                    $classNameExpanded = 'TYPO3\\CMS\\Form\\PostProcess\\' . ucfirst(strtolower($processorName)) . 'PostProcessor';
                    if (class_exists($classNameExpanded, true)) {
                        $className = $classNameExpanded;
                    }
                }
                if ($className !== false) {
                    $processor = $this->objectManager->get($className, $this->form, $processorArguments);
                    if ($processor instanceof PostProcessorInterface) {
                        $processor->setControllerContext($this->controllerContext);
                        $html .= $processor->process();
                    }
                }
            }
        }

        return $html;
    }
}
