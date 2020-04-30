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

namespace Silverback\ApiComponentBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use League\Flysystem\Filesystem;
use Silverback\ApiComponentBundle\DependencyInjection\CompilerPass\ApiPlatformCompilerPass;
use Silverback\ApiComponentBundle\DependencyInjection\CompilerPass\DoctrineCompilerPass;
use Silverback\ApiComponentBundle\DependencyInjection\CompilerPass\FlysystemCompilerPass;
use Silverback\ApiComponentBundle\DependencyInjection\CompilerPass\SerializerCompilerPass;
use Silverback\ApiComponentBundle\DependencyInjection\CompilerPass\ValidatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use function class_exists;

/**
 * @author Daniel West <daniel@silverback.is>
 */
class SilverbackApiComponentBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        if (class_exists(DoctrineOrmMappingsPass::class)) {
            $container->addCompilerPass(new DoctrineCompilerPass());
        }
        $container->addCompilerPass(new ApiPlatformCompilerPass());
        $container->addCompilerPass(new SerializerCompilerPass());
        $container->addCompilerPass(new ValidatorCompilerPass());
        if (class_exists(Filesystem::class)) {
            $container->addCompilerPass(new FlysystemCompilerPass());
        }
    }
}
