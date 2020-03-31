<?php

/*
 * This file is part of the Silverback API Component Bundle Project
 *
 * (c) Daniel West <daniel@silverback.is>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Silverback\ApiComponentBundle\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Silverback\ApiComponentBundle\Entity\Utility\IdTrait;
use Silverback\ApiComponentBundle\Entity\Utility\TimestampedInterface;
use Silverback\ApiComponentBundle\Entity\Utility\TimestampedTrait;

/**
 * @author Daniel West <daniel@silverback.is>
 * @ORM\MappedSuperclass
 */
abstract class AbstractPage implements TimestampedInterface
{
    use IdTrait;
    use TimestampedTrait;

    /**
     * @ORM\OneToOne(targetEntity="Silverback\ApiComponentBundle\Entity\Core\Route", cascade={"persist"})
     *
     * @var Route|null
     */
    public ?Route $route;

    /**
     * This will be se so that when auto-generating a route for a newly created PageTemplate / PageData, we can prepend parent routes.
     *
     * @ORM\OneToOne(targetEntity="Silverback\ApiComponentBundle\Entity\Core\Route", cascade={"persist"})
     *
     * @var Route|null
     */
    public ?Route $parentRoute;

    /**
     * @ORM\Column()
     */
    public string $title = 'Unnamed Page';

    /**
     * @ORM\Column(nullable=true)
     */
    public ?string $metaDescription;

    public function __construct()
    {
        $this->setId();
    }
}
