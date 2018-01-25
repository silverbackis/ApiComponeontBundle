<?php

namespace Silverback\ApiComponentBundle\Resources\config;

use Cocur\Slugify\SlugifyInterface;
use Silverback\ApiComponentBundle\Controller\FormPost;
use Silverback\ApiComponentBundle\Controller\FormSubmitPost;
use Silverback\ApiComponentBundle\DataProvider\Item\FormDataProvider;
use Silverback\ApiComponentBundle\Serializer\FormNormalizer;
use Silverback\ApiComponentBundle\Swagger\SwaggerDecorator;
use Silverback\ApiComponentBundle\Validator\Constraints\FormHandlerClassValidator;
use Silverback\ApiComponentBundle\Validator\Constraints\FormTypeClassValidator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->private()
    ;

    $services
        ->load('Silverback\\ApiComponentBundle\\', '../../*')
        ->exclude('../../{Entity,Migrations,Tests,Resources}')
    ;

    $services
        ->load('Silverback\\ApiComponentBundle\\EntityListener\\', '../../EntityListener')
        ->tag('doctrine.orm.entity_listener')
    ;

    $services
        ->load('Silverback\\ApiComponentBundle\\DataProvider\\Item\\', '../../DataProvider/Item')
        ->tag('api_platform.item_data_provider', [ 'priority' => 1 ])
        ->autoconfigure(false)
    ;

    $services
        ->load('Silverback\\ApiComponentBundle\\Controller\\', '../../Controller')
        ->tag('controller.service_arguments')
    ;

    $services
        ->set(FormSubmitPost::class)
        ->args(
            [
                '$formHandlers' => new TaggedIteratorArgument('silverback_api_component.form_handler')
            ]
        )
        ->tag('controller.service_arguments')
    ;

    $services
        ->alias(SlugifyInterface::class, 'slugify')
    ;

    $services
        ->set(SwaggerDecorator::class)
        ->decorate('api_platform.swagger.normalizer.documentation')
        ->args(
            [
                new Reference(SwaggerDecorator::class . '.inner')
            ]
        )
        ->autoconfigure(false)
    ;

    $services
        ->set(FormTypeClassValidator::class)
        ->tag('validator.constraint_validator')
        ->args(
            [
                '$formTypes' => new TaggedIteratorArgument('silverback_api_component.form_type')
            ]
        )
    ;

    $services
        ->set(FormHandlerClassValidator::class)
        ->tag('validator.constraint_validator')
        ->args(
            [
                '$formHandlers' => new TaggedIteratorArgument('silverback_api_component.form_handler')
            ]
        )
    ;
};