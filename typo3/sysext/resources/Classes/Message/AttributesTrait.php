<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Message;

trait AttributesTrait
{

    protected readonly array $attributes;

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, $value): MessageAttributesInterface
    {
        $attributes = $this->attributes;
        $attributes[$name] = $value;
        return $this->mutate(['attributes' => $attributes]);
    }

    public function withoutAttribute(string $name): MessageAttributesInterface
    {
        $attributes = $this->attributes;
        unset($attributes[$name]);
        return $this->mutate(['attributes' => $attributes]);
    }
}
