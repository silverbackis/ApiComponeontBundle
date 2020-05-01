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

namespace Silverback\ApiComponentBundle\AnnotationReader;

use Silverback\ApiComponentBundle\Annotation\Uploadable;
use Silverback\ApiComponentBundle\Annotation\UploadableField;
use Silverback\ApiComponentBundle\Exception\InvalidArgumentException;
use Silverback\ApiComponentBundle\Exception\UnsupportedAnnotationException;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class UploadableAnnotationReader extends AbstractAnnotationReader
{
    /**
     * @param object|string $class
     */
    public function getConfiguration($class): Uploadable
    {
        return $this->getClassAnnotationConfiguration($class, Uploadable::class);
    }

    public function isFieldConfigured(\ReflectionProperty $property): bool
    {
        try {
            $this->getPropertyConfiguration($property);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    public function getPropertyConfiguration(\ReflectionProperty $property): UploadableField
    {
        /** @var UploadableField|null $annotation */
        if (!$annotation = $this->reader->getPropertyAnnotation($property, UploadableField::class)) {
            throw new InvalidArgumentException(sprintf('%s::%s does not have %s annotation', $property->getDeclaringClass()->getName(), $property->getName(), UploadableField::class));
        }

        return $annotation;
    }

    /**
     * @param object|string $data
     */
    public function getConfiguredProperties($data, bool $skipUploadableCheck = false, bool $returnConfigurations = true): iterable
    {
        if (!$skipUploadableCheck && !$this->isConfigured($data)) {
            throw new UnsupportedAnnotationException(sprintf('Cannot get field configuration for %s: is it not configured as Uploadable', \is_string($data) ? $data : \get_class($data)));
        }

        $found = false;
        $reflectionProperties = (new \ReflectionClass($data))->getProperties();
        foreach ($reflectionProperties as $reflectionProperty) {
            try {
                $config = $this->getPropertyConfiguration($reflectionProperty);
                if ($returnConfigurations) {
                    yield $reflectionProperty->getName() => $config;
                } else {
                    yield $reflectionProperty->getName();
                }
                $found = true;
            } catch (InvalidArgumentException $e) {
            }
        }
        if (!$found) {
            throw new UnsupportedAnnotationException(sprintf('No field configurations on your Uploadable component %s.', \is_string($data) ? $data : \get_class($data)));
        }
    }
}