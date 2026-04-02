<?php

declare(strict_types=1);

namespace Vendor\MySitePackage\Domain\Factory;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Factory\AbstractFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;

final class CustomFormFactory extends AbstractFormFactory
{
    public function build(
        array $configuration,
        ?string $prototypeName = null,
        ?ServerRequestInterface $request = null,
    ): FormDefinition {
        $prototypeName ??= 'standard';
        $configurationService = GeneralUtility::makeInstance(
            ConfigurationService::class,
        );
        $prototypeConfiguration = $configurationService->getPrototypeConfiguration(
            $prototypeName,
        );

        $form = GeneralUtility::makeInstance(
            FormDefinition::class,
            'ContactForm',
            $prototypeConfiguration,
        );
        $form->setRenderingOption('controllerAction', 'index');

        // Page 1 – personal data
        $page1 = $form->createPage('page1');

        /** @var AbstractFormElement $name */
        $name = $page1->createElement('name', 'Text');
        $name->setLabel('Name');
        $name->createValidator('NotEmpty');

        /** @var AbstractFormElement $email */
        $email = $page1->createElement('email', 'Text');
        $email->setLabel('Email');

        // Page 2 – message
        $page2 = $form->createPage('page2');

        /** @var AbstractFormElement $message */
        $message = $page2->createElement('message', 'Textarea');
        $message->setLabel('Message');
        $message->createValidator('StringLength', ['minimum' => 5, 'maximum' => 500]);

        // Radio buttons
        /** @var AbstractFormElement $subject */
        $subject = $page2->createElement('subject', 'RadioButton');
        $subject->setProperty('options', [
            'general' => 'General inquiry',
            'support' => 'Support request',
        ]);
        $subject->setLabel('Subject');

        // Finisher – send email
        $form->createFinisher('EmailToSender', [
            'subject' => 'Contact form submission',
            'recipients' => [
                'info@example.com' => 'My Company',
            ],
            'senderAddress' => 'noreply@example.com',
        ]);

        $this->triggerFormBuildingFinished($form);

        return $form;
    }
}
