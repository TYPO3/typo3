<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Definition\Metadata;

final class Version implements VersionInterface
{


    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly bool   $served,
        private readonly string $controllerServiceId
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isServed(): bool
    {
        return $this->served;
    }

    public function getControllerServiceId(): string
    {
        return $this->controllerServiceId;
    }

    public function __toString(): string
    {
        return $this->getId();
    }


}
