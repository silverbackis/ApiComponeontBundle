<?php

namespace Silverback\ApiComponentBundle\Entity\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Silverback\ApiComponentBundle\Entity\Content\ComponentGroup;
use Silverback\ApiComponentBundle\Entity\Content\ContentInterface;
use Silverback\ApiComponentBundle\Entity\ValidComponentInterface;

interface ComponentInterface extends ValidComponentInterface
{
    /**
     * ComponentInterface constructor.
     */
    public function __construct();

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return null|string
     */
    public function getClassName(): ?string;

    /**
     * @param null|string $className
     * @return AbstractComponent
     */
    public function setClassName(?string $className): AbstractComponent;

    /**
     * @param ContentInterface $content
     * @param bool|null $sortLast
     * @return AbstractComponent
     */
    public function addLocation(ContentInterface $content, ?bool $sortLast = null): AbstractComponent;

    /**
     * @param ContentInterface $content
     * @return AbstractComponent
     */
    public function removeLocation(ContentInterface $content): AbstractComponent;

    /**
     * @param array $componentGroups
     * @return AbstractComponent
     */
    public function setComponentGroups(array $componentGroups): AbstractComponent;

    /**
     * @param ComponentGroup $componentGroup
     * @return AbstractComponent
     */
    public function addComponentGroup(ComponentGroup $componentGroup): AbstractComponent;

    /**
     * @param ComponentGroup $componentGroup
     * @return AbstractComponent
     */
    public function removeComponentGroup(ComponentGroup $componentGroup): AbstractComponent;

    /**
     * @return ArrayCollection|ComponentGroup[]
     */
    public function getComponentGroups(): Collection;

    /**
     * @return string
     */
    public static function getComponentName(): string;
}