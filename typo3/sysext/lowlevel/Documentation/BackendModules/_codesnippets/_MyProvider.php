<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Configuration\Processor\ConfigurationModule;

use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderInterface;

final class MyProvider implements ProviderInterface
{
    private string $identifier;

    public function __invoke(array $attributes): self
    {
        $this->identifier = $attributes['identifier'];
        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLabel(): string
    {
        return 'My custom configuration';
    }

    public function getConfiguration(): array
    {
        $myCustomConfiguration = [
            // the custom configuration
        ];

        return $myCustomConfiguration;
    }
}
