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

namespace Silverback\ApiComponentBundle\Resources\config;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\EventListener\EventPriorities;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Liip\ImagineBundle\Service\FilterService;
use Psr\Container\ContainerInterface;
use Silverback\ApiComponentBundle\Action\File\FileAction;
use Silverback\ApiComponentBundle\Action\Form\FormPostPatchAction;
use Silverback\ApiComponentBundle\Action\User\EmailAddressVerifyAction;
use Silverback\ApiComponentBundle\Action\User\PasswordRequestAction;
use Silverback\ApiComponentBundle\Action\User\PasswordUpdateAction;
use Silverback\ApiComponentBundle\ApiPlatform\Metadata\Resource\FileResourceMetadataFactory;
use Silverback\ApiComponentBundle\ApiPlatform\Metadata\Resource\RoutingPrefixResourceMetadataFactory;
use Silverback\ApiComponentBundle\Command\FormCachePurgeCommand;
use Silverback\ApiComponentBundle\Command\UserCreateCommand;
use Silverback\ApiComponentBundle\DataTransformer\CollectionOutputDataTransformer;
use Silverback\ApiComponentBundle\DataTransformer\FormOutputDataTransformer;
use Silverback\ApiComponentBundle\DataTransformer\PageTemplateOutputDataTransformer;
use Silverback\ApiComponentBundle\Doctrine\Extension\ORM\PublishableExtension;
use Silverback\ApiComponentBundle\Doctrine\Extension\ORM\TablePrefixExtension;
use Silverback\ApiComponentBundle\Entity\User\AbstractUser;
use Silverback\ApiComponentBundle\Event\FormSuccessEvent;
use Silverback\ApiComponentBundle\EventListener\Api\PublishableEventListener;
use Silverback\ApiComponentBundle\EventListener\Doctrine\FileListener;
use Silverback\ApiComponentBundle\EventListener\Doctrine\PublishableListener;
use Silverback\ApiComponentBundle\EventListener\Doctrine\TimestampedListener;
use Silverback\ApiComponentBundle\EventListener\Doctrine\UserListener;
use Silverback\ApiComponentBundle\EventListener\Form\User\NewEmailAddressListener;
use Silverback\ApiComponentBundle\EventListener\Form\User\UserRegisterListener;
use Silverback\ApiComponentBundle\EventListener\Jwt\JwtCreatedEventListener;
use Silverback\ApiComponentBundle\EventListener\Mailer\MessageEventListener;
use Silverback\ApiComponentBundle\Factory\Form\FormFactory;
use Silverback\ApiComponentBundle\Factory\Form\FormViewFactory;
use Silverback\ApiComponentBundle\Factory\Mailer\User\AbstractUserEmailFactory;
use Silverback\ApiComponentBundle\Factory\Mailer\User\ChangeEmailVerificationEmailFactory;
use Silverback\ApiComponentBundle\Factory\Mailer\User\PasswordChangedEmailFactory;
use Silverback\ApiComponentBundle\Factory\Mailer\User\PasswordResetEmailFactory;
use Silverback\ApiComponentBundle\Factory\Mailer\User\UserEnabledEmailFactory;
use Silverback\ApiComponentBundle\Factory\Mailer\User\UsernameChangedEmailFactory;
use Silverback\ApiComponentBundle\Factory\Mailer\User\WelcomeEmailFactory;
use Silverback\ApiComponentBundle\Factory\Response\ResponseFactory;
use Silverback\ApiComponentBundle\Factory\User\UserFactory;
use Silverback\ApiComponentBundle\Flysystem\FilesystemProvider;
use Silverback\ApiComponentBundle\Form\Cache\FormCachePurger;
use Silverback\ApiComponentBundle\Form\Handler\FormSubmitHandler;
use Silverback\ApiComponentBundle\Form\Type\User\ChangePasswordType;
use Silverback\ApiComponentBundle\Form\Type\User\NewEmailAddressType;
use Silverback\ApiComponentBundle\Form\Type\User\UserLoginType;
use Silverback\ApiComponentBundle\Form\Type\User\UserRegisterType;
use Silverback\ApiComponentBundle\Helper\FileHelper;
use Silverback\ApiComponentBundle\Helper\PublishableHelper;
use Silverback\ApiComponentBundle\Helper\TimestampedHelper;
use Silverback\ApiComponentBundle\Helper\UploadsHelper;
use Silverback\ApiComponentBundle\Mailer\UserMailer;
use Silverback\ApiComponentBundle\Manager\User\EmailAddressManager;
use Silverback\ApiComponentBundle\Manager\User\PasswordManager;
use Silverback\ApiComponentBundle\Repository\Core\LayoutRepository;
use Silverback\ApiComponentBundle\Repository\Core\RouteRepository;
use Silverback\ApiComponentBundle\Repository\User\UserRepository;
use Silverback\ApiComponentBundle\Security\TokenAuthenticator;
use Silverback\ApiComponentBundle\Security\UserChecker;
use Silverback\ApiComponentBundle\Serializer\ContextBuilder\PublishableContextBuilder;
use Silverback\ApiComponentBundle\Serializer\ContextBuilder\TimestampedContextBuilder;
use Silverback\ApiComponentBundle\Serializer\ContextBuilder\UserContextBuilder;
use Silverback\ApiComponentBundle\Serializer\MappingLoader\PublishableLoader;
use Silverback\ApiComponentBundle\Serializer\MappingLoader\TimestampedLoader;
use Silverback\ApiComponentBundle\Serializer\Normalizer\FileNormalizer;
use Silverback\ApiComponentBundle\Serializer\Normalizer\MetadataNormalizer;
use Silverback\ApiComponentBundle\Serializer\Normalizer\PersistedNormalizer;
use Silverback\ApiComponentBundle\Serializer\Normalizer\PublishableNormalizer;
use Silverback\ApiComponentBundle\Serializer\SerializeFormatResolver;
use Silverback\ApiComponentBundle\Utility\RefererUrlHelper;
use Silverback\ApiComponentBundle\Validator\Constraints\FormTypeClassValidator;
use Silverback\ApiComponentBundle\Validator\Constraints\NewEmailAddressValidator;
use Silverback\ApiComponentBundle\Validator\MappingLoader\UploadsLoader;
use Silverback\ApiComponentBundle\Validator\PublishableValidator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

/*
 * @author Daniel West <daniel@silverback.is>
 */
return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services
        ->set(AbstractUserEmailFactory::class)
        ->abstract()
        ->args([
            '$container' => new Reference(ContainerInterface::class),
            '$eventDispatcher' => new Reference(EventDispatcherInterface::class),
        ]);

    $services
        ->set(ChangeEmailVerificationEmailFactory::class)
        ->parent(AbstractUserEmailFactory::class)
        ->tag('container.service_subscriber');

    $services
        ->set(ChangePasswordType::class)
        ->args([new Reference(Security::class)])
        ->tag('form.type');

    $services
        ->set(CollectionOutputDataTransformer::class)
        ->tag('api_platform.data_transformer')
        ->args([
            new Reference(RequestStack::class),
            new Reference(ResourceMetadataFactoryInterface::class),
            new Reference(OperationPathResolverInterface::class),
            new Reference(ContextAwareCollectionDataProviderInterface::class),
            new Reference(IriConverterInterface::class),
            new Reference(NormalizerInterface::class),
            new Reference(SerializeFormatResolver::class),
            new Reference(UrlHelper::class),
        ]);

    $services
        ->set(FileAction::class)
        ->tag('controller.service_arguments');

    $services
        ->set(FilesystemProvider::class)
        ->args([tagged_locator(FilesystemProvider::FILESYSTEM_ADAPTER_TAG, 'alias')]);

    $services
        ->set(EmailAddressManager::class)
        ->args([
            new Reference(EntityManagerInterface::class),
            new Reference(UserRepository::class),
        ]);

    $services
        ->set(EmailAddressVerifyAction::class)
        ->args([
            new Reference(SerializerInterface::class),
            new Reference(SerializeFormatResolver::class),
            new Reference(ResponseFactory::class),
            new Reference(EmailAddressManager::class),
        ])
        ->tag('controller.service_arguments');

    $services
        ->set(FileHelper::class)
        ->args([
            new Reference('annotations.reader'),
            new Reference('doctrine'),
            new Reference(FilesystemProvider::class),
        ]);

    $services
        ->set(FileListener::class)
        ->args([
            new Reference(FileHelper::class),
            new Reference(UploadsHelper::class),
        ])
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata'])
        ->tag('doctrine.orm.entity_listener');

    $services
        ->set(FileNormalizer::class)
        ->autoconfigure(false)
        ->args([
            new Reference(FileHelper::class),
        ])
        ->tag('serializer.normalizer', ['priority' => -499]);

    $services
        ->set(FileResourceMetadataFactory::class)
        ->decorate('api_platform.metadata.resource.metadata_factory')
        ->args([
            new Reference(FileResourceMetadataFactory::class . '.inner'),
            new Reference(FileHelper::class),
            new Reference('api_platform.path_segment_name_generator'),
        ])
        ->autoconfigure(false);

    $services
        ->set(FormCachePurgeCommand::class)
        ->tag('console.command')
        ->args([
            new Reference(FormCachePurger::class),
            new Reference(EventDispatcherInterface::class),
        ]);

    $services
        ->set(FormCachePurger::class)
        ->args([
            new Reference(EntityManagerInterface::class),
            new Reference(EventDispatcherInterface::class),
        ]);

    $services
        ->set(FormFactory::class)
        ->args([
            new Reference(FormFactoryInterface::class),
            new Reference(RouterInterface::class),
        ]);

    $services
        ->set(FormOutputDataTransformer::class)
        ->tag('api_platform.data_transformer')
        ->args([new Reference(FormViewFactory::class)]);

    $services
        ->set(FormPostPatchAction::class)
        ->args([
            new Reference(SerializerInterface::class),
            new Reference(SerializeFormatResolver::class),
            new Reference(ResponseFactory::class),
            new Reference(FormSubmitHandler::class),
        ])
        ->tag('controller.service_arguments');

    $services
        ->set(FormSubmitHandler::class)
        ->args([
            new Reference(FormFactory::class),
            new Reference(EventDispatcherInterface::class),
            new Reference(SerializerInterface::class),
        ]);

    $services
        ->set(FormTypeClassValidator::class)
        ->tag('validator.constraint_validator')
        ->args(
            [
                '$formTypes' => new TaggedIteratorArgument('silverback_api_component.form_type'),
            ]
        );

    $services
        ->set(FormViewFactory::class)
        ->args([new Reference(FormFactory::class)]);

    $services
        ->set(JwtCreatedEventListener::class)
        ->args([
            new Reference(RoleHierarchy::class),
        ])
        ->tag('kernel.event_listener', ['event' => Events::JWT_CREATED, 'method' => 'updateTokenRoles']);

    $services
        ->set(LayoutRepository::class)
        ->args([
            new Reference(ManagerRegistry::class),
        ])
        ->tag('doctrine.repository_service');

    $services
        ->set(MessageEventListener::class)
        ->tag('kernel.event_listener', ['event' => MessageEvent::class])
        ->args([
            '%env(MAILER_EMAIL)%',
        ]);

    $services
        ->set(MetadataNormalizer::class)
        ->autoconfigure(false)
        ->args([
            '', // set in dependency injection
        ])
        ->tag('serializer.normalizer', ['priority' => -500]);

    $services
        ->set(NewEmailAddressListener::class)
        ->args([new Reference(EntityManagerInterface::class)])
        ->tag('kernel.event_listener', ['event' => FormSuccessEvent::class]);

    $services
        ->set(NewEmailAddressType::class)
        ->args([new Reference(Security::class)])
        ->tag('form.type');

    $services
        ->set(NewEmailAddressValidator::class)
        ->args([
            new Reference(UserRepository::class),
        ])
        ->tag('validator.constraint_validator');

    $services
        ->set(PageTemplateOutputDataTransformer::class)
        ->tag('api_platform.data_transformer')
        ->args([
            new Reference(LayoutRepository::class),
        ]);

    $services
        ->set(PasswordChangedEmailFactory::class)
        ->parent(AbstractUserEmailFactory::class)
        ->tag('container.service_subscriber');

    $services
        ->set(PasswordManager::class)
        ->args([
            new Reference(UserMailer::class),
            new Reference(EntityManagerInterface::class),
            new Reference(ValidatorInterface::class),
            new Reference(UserRepository::class),
        ]);

    $services
        ->set(PasswordResetEmailFactory::class)
        ->parent(AbstractUserEmailFactory::class)
        ->tag('container.service_subscriber');

    $services
        ->set(PasswordRequestAction::class)
        ->args($passwordActionArgs = [
            new Reference(SerializerInterface::class),
            new Reference(SerializeFormatResolver::class),
            new Reference(ResponseFactory::class),
            new Reference(PasswordManager::class),
        ])
        ->tag('controller.service_arguments');

    $services
        ->set(PasswordUpdateAction::class)
        ->args($passwordActionArgs)
        ->tag('controller.service_arguments');

    $services
        ->set(PersistedNormalizer::class)
        ->autoconfigure(false)
        ->args([
            new Reference(EntityManagerInterface::class),
            new Reference(ResourceClassResolverInterface::class),
        ])
        ->tag('serializer.normalizer', ['priority' => -499]);

    $services
        ->set(PublishableContextBuilder::class)
        ->decorate('api_platform.serializer.context_builder')
        ->args([
            new Reference(PublishableContextBuilder::class . '.inner'),
            new Reference(PublishableHelper::class),
        ])
        ->autoconfigure(false);

    $services
        ->set(PublishableEventListener::class)
        ->args([
            new Reference(PublishableHelper::class),
            new Reference('doctrine'),
            new Reference('api_platform.validator'),
        ])
        ->tag('kernel.event_listener', ['event' => RequestEvent::class, 'priority' => EventPriorities::POST_READ, 'method' => 'onPostRead'])
        ->tag('kernel.event_listener', ['event' => ViewEvent::class, 'priority' => EventPriorities::PRE_WRITE, 'method' => 'onPreWrite'])
        ->tag('kernel.event_listener', ['event' => RequestEvent::class, 'priority' => EventPriorities::POST_DESERIALIZE, 'method' => 'onPostDeserialize'])
        ->tag('kernel.event_listener', ['event' => ResponseEvent::class, 'priority' => EventPriorities::POST_RESPOND, 'method' => 'onPostRespond']);

    $services
        ->set(PublishableHelper::class)
        ->args([
            new Reference('annotations.reader'),
            new Reference('doctrine'),
            new Reference('security.authorization_checker'),
            '', // $permission: set in SilverbackApiComponentExtension
        ]);

    $services
        ->set(PublishableListener::class)
        ->args([new Reference(PublishableHelper::class)])
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata']);

    // High priority for item because of queryBuilder reset
    $services
        ->set(PublishableExtension::class)
        ->args([
            new Reference(PublishableHelper::class),
            new Reference('request_stack'),
            new Reference('doctrine'),
        ])
        ->tag('api_platform.doctrine.orm.query_extension.item', ['priority' => 100])
        ->tag('api_platform.doctrine.orm.query_extension.collection');

    $services
        ->set(PublishableNormalizer::class)
        ->autoconfigure(false)
        ->args([
            new Reference(PublishableHelper::class),
            new Reference('doctrine'),
            new Reference('request_stack'),
            new Reference('api_platform.validator'),
        ])->tag('serializer.normalizer', ['priority' => -400]);

    $services
        ->set(PublishableValidator::class)
        ->decorate('api_platform.validator')
        ->args([
            new Reference(PublishableValidator::class . '.inner'),
            new Reference(PublishableHelper::class),
        ]);

    $services
        ->set(PublishableLoader::class)
        ->args([
            new Reference('annotations.reader'),
        ]);

    $services
        ->set(ResponseFactory::class)
        ->args([
            new Reference(SerializerInterface::class),
            new Reference(SerializeFormatResolver::class),
        ]);

    $services
        ->set(RefererUrlHelper::class)
        ->args([
            new Reference(RequestStack::class),
        ]);

    $services
        ->set(RouteRepository::class)
        ->args([
            new Reference(ManagerRegistry::class),
        ])
        ->tag('doctrine.repository_service');

    $services
        ->set(RoutingPrefixResourceMetadataFactory::class)
        ->decorate('api_platform.metadata.resource.metadata_factory')
        ->args([
            new Reference(RoutingPrefixResourceMetadataFactory::class . '.inner'),
        ]);

    $services
        ->set(SerializeFormatResolver::class)
        ->args([
            new Reference(RequestStack::class),
            'jsonld',
        ]);

    $services
        ->set(TimestampedContextBuilder::class)
        ->decorate('api_platform.serializer.context_builder')
        ->args([
            new Reference(TimestampedContextBuilder::class . '.inner'),
        ])
        ->autoconfigure(false);

    $services
        ->set(TimestampedHelper::class)
        ->args([
            new Reference('annotations.reader'),
            new Reference('doctrine'),
        ]);

    $services
        ->set(TimestampedLoader::class)
        ->args([
            new Reference('annotations.reader'),
        ]);

    $services
        ->set(TablePrefixExtension::class)
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata']);

    $getTimestampedListenerTagArgs = static function ($event) {
        return [
            'event' => $event,
            'method' => $event,
        ];
    };
    $services
        ->set(TimestampedListener::class)
        ->args([
            new Reference(TimestampedHelper::class),
            new Reference(ManagerRegistry::class),
        ])
        ->tag('doctrine.event_listener', $getTimestampedListenerTagArgs('loadClassMetadata'))
        ->tag('doctrine.event_listener', $getTimestampedListenerTagArgs('prePersist'))
        ->tag('doctrine.event_listener', $getTimestampedListenerTagArgs('preUpdate'));

    $services
        ->set(TokenAuthenticator::class)
        ->args([
            new Reference(Security::class),
            new Reference(ResponseFactory::class),
        ]);

    $services
        ->set(UploadsHelper::class)
        ->args([
            new Reference('annotations.reader'),
            new Reference('doctrine'),
        ]);

    $services
        ->set(UploadsLoader::class)
        ->args([
            new Reference(UploadsHelper::class),
        ]);

    $services
        ->set(UserChecker::class);

    $services
        ->set(UserContextBuilder::class)
        ->decorate('api_platform.serializer.context_builder')
        ->args([
            new Reference(UserContextBuilder::class . '.inner'),
            new Reference(AuthorizationCheckerInterface::class),
        ])
        ->autoconfigure(false);

    $services
        ->set(UserCreateCommand::class)
        ->tag('console.command')
        ->args([
            new Reference(UserFactory::class),
        ]);

    $services
        ->set(UserEnabledEmailFactory::class)
        ->parent(AbstractUserEmailFactory::class)
        ->tag('container.service_subscriber');

    $services
        ->set(UserFactory::class)
        ->args([
            new Reference(EntityManagerInterface::class),
            new Reference(ValidatorInterface::class),
            new Reference(UserRepository::class),
        ]);

    $getUserListenerTagArgs = static function ($event) {
        return [
            'event' => $event,
            'method' => $event,
            'entity' => AbstractUser::class,
            'lazy' => true,
        ];
    };
    $services
        ->set(UserListener::class)
        ->tag('doctrine.orm.entity_listener', $getUserListenerTagArgs('prePersist'))
        ->tag('doctrine.orm.entity_listener', $getUserListenerTagArgs('postPersist'))
        ->tag('doctrine.orm.entity_listener', $getUserListenerTagArgs('preUpdate'))
        ->tag('doctrine.orm.entity_listener', $getUserListenerTagArgs('postUpdate'))
        ->args([
            new Reference(UserPasswordEncoderInterface::class),
            new Reference(UserMailer::class),
        ]);

    $services
        ->set(UserLoginType::class)
        ->args([new Reference(RouterInterface::class)])
        ->tag('form.type');

    $services
        ->set(UserMailer::class)
        ->args([
            new Reference(MailerInterface::class),
            new Reference(ContainerInterface::class),
        ])
        ->tag('container.service_subscriber');

    $services
        ->set(UsernameChangedEmailFactory::class)
        ->parent(AbstractUserEmailFactory::class)
        ->tag('container.service_subscriber');

    $services
        ->set(UserRegisterListener::class)
        ->args([new Reference(EntityManagerInterface::class)])
        ->tag('kernel.event_listener', ['event' => FormSuccessEvent::class]);

    $services
        ->set(UserRegisterType::class)
        ->tag('form.type');

    $services
        ->set(UserRepository::class)
        ->args([
            new Reference(ManagerRegistry::class),
        ])
        ->tag('doctrine.repository_service');

    $services
        ->set(WelcomeEmailFactory::class)
        ->parent(AbstractUserEmailFactory::class)
        ->tag('container.service_subscriber');

    $services->alias(ContextAwareCollectionDataProviderInterface::class, 'api_platform.collection_data_provider');
    $services->alias(Environment::class, 'twig');
    $services->alias(FilterService::class, 'liip_imagine.service.filter');
    $services->alias(OperationPathResolverInterface::class, 'api_platform.operation_path_resolver.router');
    $services->alias(RoleHierarchy::class, 'security.role_hierarchy');
};
