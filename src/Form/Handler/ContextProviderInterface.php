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

namespace Silverback\ApiComponentBundle\Form\Handler;

/**
 * @author Daniel West <daniel@silverback.is>
 */
interface ContextProviderInterface
{
    public function getContext(): ?array;
}
