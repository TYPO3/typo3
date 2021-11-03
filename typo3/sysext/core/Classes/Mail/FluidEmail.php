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

namespace TYPO3\CMS\Core\Mail;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

/**
 * Send out templated HTML/plain text emails with Fluid.
 */
class FluidEmail extends Email
{
    public const FORMAT_HTML = 'html';
    public const FORMAT_PLAIN = 'plain';
    public const FORMAT_BOTH = 'both';

    /**
     * @var string[]
     */
    protected $format = ['html', 'plain'];

    /**
     * @var string
     */
    protected $templateName = 'Default';

    /**
     * @var StandaloneView
     */
    protected $view;

    public function __construct(TemplatePaths $templatePaths = null, Headers $headers = null, AbstractPart $body = null)
    {
        parent::__construct($headers, $body);
        $this->initializeView($templatePaths);
    }

    protected function initializeView(TemplatePaths $templatePaths = null): void
    {
        $templatePaths = $templatePaths ?? new TemplatePaths($GLOBALS['TYPO3_CONF_VARS']['MAIL']);
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->getRenderingContext()->setTemplatePaths($templatePaths);
        $this->view->assignMultiple($this->getDefaultVariables());
        $this->format($GLOBALS['TYPO3_CONF_VARS']['MAIL']['format'] ?? self::FORMAT_BOTH);
    }

    public function format(string $format)
    {
        switch ($format) {
            case self::FORMAT_BOTH:
                $this->format = [self::FORMAT_HTML, self::FORMAT_PLAIN];
                break;
            case self::FORMAT_HTML:
                $this->format = [self::FORMAT_HTML];
                break;
            case self::FORMAT_PLAIN:
                $this->format = [self::FORMAT_PLAIN];
                break;
            default:
                throw new \InvalidArgumentException('Setting FluidEmail->format() must be either "html", "plain" or "both", no other formats are currently supported', 1580743847);
        }
        return $this;
    }

    public function setTemplate(string $templateName)
    {
        $this->templateName = $templateName;
        return $this;
    }

    public function assign($key, $value)
    {
        $this->view->assign($key, $value);
        return $this;
    }

    public function assignMultiple(array $values)
    {
        $this->view->assignMultiple($values);
        return $this;
    }

    /*
     * Shorthand setters
     */
    public function setRequest(ServerRequestInterface $request): self
    {
        $this->view->assign('request', $request);
        if ($request->getAttribute('normalizedParams') instanceof NormalizedParams) {
            $this->view->assign('normalizedParams', $request->getAttribute('normalizedParams'));
        } else {
            $this->view->assign('normalizedParams', NormalizedParams::createFromServerParams($_SERVER));
        }
        return $this;
    }

    protected function getDefaultVariables(): array
    {
        return [
            'typo3' => [
                'sitename' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
                'formats' => [
                    'date' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
                    'time' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
                ],
                'systemConfiguration' => $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'],
                'information' => GeneralUtility::makeInstance(Typo3Information::class),
            ],
        ];
    }

    public function ensureValidity()
    {
        $this->generateTemplatedBody();
        parent::ensureValidity();
    }

    public function getBody(): AbstractPart
    {
        $this->generateTemplatedBody();
        return parent::getBody();
    }

    /**
     * @return resource|string|null
     */
    public function getHtmlBody(bool $forceBodyGeneration = false)
    {
        if ($forceBodyGeneration) {
            $this->generateTemplatedBody('html');
        }
        return parent::getHtmlBody();
    }

    /**
     * @return resource|string|null
     */
    public function getTextBody(bool $forceBodyGeneration = false)
    {
        if ($forceBodyGeneration) {
            $this->generateTemplatedBody('plain');
        }
        return parent::getTextBody();
    }

    /**
     * @return ViewHelperVariableContainer
     * @internal Only used for ext:form, not part of TYPO3 Core API.
     */
    public function getViewHelperVariableContainer(): ViewHelperVariableContainer
    {
        return $this->view->getRenderingContext()->getViewHelperVariableContainer();
    }

    protected function generateTemplatedBody(string $forceFormat = ''): void
    {
        // Use a local variable to allow forcing a specific format
        $format = $forceFormat ? [$forceFormat] : $this->format;

        if (!$this->view) {
            $this->initializeView();
        }
        if (in_array(static::FORMAT_HTML, $format, true)) {
            $this->html($this->renderContent('html'));
        }
        if (in_array(static::FORMAT_PLAIN, $format, true)) {
            $this->text(trim($this->renderContent('txt')));
        }

        $subjectFromTemplate = $this->view->renderSection(
            'Subject',
            $this->view->getRenderingContext()->getVariableProvider()->getAll(),
            true
        );
        if (!empty($subjectFromTemplate)) {
            $this->subject($subjectFromTemplate);
        }
    }

    protected function renderContent(string $format): string
    {
        $this->view->setFormat($format);
        $this->view->setTemplate($this->templateName);
        return $this->view->render();
    }
}
