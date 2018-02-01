<?php

namespace Silverback\ApiComponentBundle\Entity\Component\Nav;

use Doctrine\ORM\Mapping as ORM;
use Silverback\ApiComponentBundle\Entity\Component\AbstractComponent;
use Silverback\ApiComponentBundle\Entity\Component\SortableTrait;
use Silverback\ApiComponentBundle\Entity\Route\Route;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AbstractNavItem
 * @package Silverback\ApiComponentBundle\Entity\Component\Nav
 * @ORM\Entity()
 * @ORM\Table(name="nav_item")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *     "navbar_item" = "Silverback\ApiComponentBundle\Entity\Component\Nav\Navbar\NavbarItem",
 *     "menu_item" = "Silverback\ApiComponentBundle\Entity\Component\Nav\Menu\MenuItem",
 *     "tab" = "Silverback\ApiComponentBundle\Entity\Component\Nav\Tabs\TabItem"
 * })
 */
abstract class AbstractNavItem implements NavItemInterface
{
    use SortableTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @var AbstractNav
     */
    protected $nav;

    /**
     * @ORM\ManyToOne(targetEntity="\Silverback\ApiComponentBundle\Entity\Route\Route")
     * @ORM\JoinColumn(referencedColumnName="route", nullable=true)
     * @Groups({"component"})
     * @var null|Route
     */
    private $route;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"component"})
     * @var null|string
     */
    private $fragment;

    /**
     * @ORM\Column(type="string")
     * @Groups({"component"})
     * @var string
     */
    private $label;

    /**
     * @ORM\OneToOne(targetEntity="\Silverback\ApiComponentBundle\Entity\Component\AbstractComponent")
     * @Assert\Type({"\Silverback\ApiComponentBundle\Entity\Component\Nav\AbstractNav"})
     * @Groups({"component"})
     * @var null|AbstractNav
     */
    private $child;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return AbstractNav
     */
    public function getNav(): AbstractNav
    {
        return $this->nav;
    }

    /**
     * @param AbstractNav $nav
     */
    public function setNav(AbstractNav $nav): void
    {
        $this->nav = $nav;
    }

    /**
     * @return null|Route
     */
    public function getRoute(): ?Route
    {
        return $this->route;
    }

    /**
     * @param null|Route $route
     */
    public function setRoute(Route $route = null): void
    {
        $this->route = $route;
    }

    /**
     * @return null|string
     */
    public function getFragment(): ?string
    {
        return $this->fragment;
    }

    /**
     * @param null|string $fragment
     */
    public function setFragment(?string $fragment): void
    {
        $this->fragment = $fragment;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return AbstractNav|null
     */
    public function getChild(): ?AbstractNav
    {
        return $this->child;
    }

    /**
     * @param AbstractNav|null $child
     */
    public function setChild(?AbstractNav $child): void
    {
        $this->child = $child;
    }
}
