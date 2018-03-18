<?php

namespace Silverback\ApiComponentBundle\Serializer;

use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Silverback\ApiComponentBundle\Entity\Content\Component\Form\Form;
use Silverback\ApiComponentBundle\Entity\Content\FileInterface;
use Silverback\ApiComponentBundle\Entity\Content\Page;
use Silverback\ApiComponentBundle\Entity\Layout\Layout;
use Silverback\ApiComponentBundle\Factory\Entity\Content\Component\Form\FormViewFactory;
use Silverback\ApiComponentBundle\Imagine\PathResolver;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class ApiNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    private $decorated;
    private $imagineCacheManager;
    private $formViewFactory;
    private $pathResolver;
    private $em;
    private $projectDir;

    /**
     * FileNormalizer constructor.
     * @param NormalizerInterface $decorated
     * @param CacheManager $imagineCacheManager
     * @param FormViewFactory $formViewFactory
     * @param PathResolver $pathResolver
     * @param EntityManagerInterface $entityManager
     * @param string $projectDir
     */
    public function __construct(
        NormalizerInterface $decorated,
        CacheManager $imagineCacheManager,
        FormViewFactory $formViewFactory,
        PathResolver $pathResolver,
        EntityManagerInterface $entityManager,
        string $projectDir = '/'
    ) {
        if (!$decorated instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException(sprintf('The decorated normalizer must implement the %s.', DenormalizerInterface::class));
        }
        $this->decorated = $decorated;
        $this->imagineCacheManager = $imagineCacheManager;
        $this->formViewFactory = $formViewFactory;
        $this->pathResolver = $pathResolver;
        $this->em = $entityManager;
        $this->projectDir = $projectDir;
    }

    /**
     * @param mixed $data
     * @param string|null $format
     * @return bool
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    /**
     * @param $object
     * @param string|null $format
     * @param array $context
     * @return array|bool|float|int|string
     * @throws \Symfony\Component\Serializer\Exception\LogicException
     * @throws \Symfony\Component\Serializer\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Serializer\Exception\CircularReferenceException
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if ($object instanceof Page) {
            if (!$object->getLayout()) {
                $object->setLayout($this->em->getRepository(Layout::class)->findOneBy(['default' => true]));
            }
        }
        if ($object instanceof Form && !$object->getForm()) {
            $object->setForm($this->formViewFactory->create($object));
        }
        $data = $this->decorated->normalize($object, $format, $context);

        if ($object instanceof FileInterface) {
            $data = array_merge($data, $this->getFileData($object));
        }
        return $data;
    }

    /**
     * @param \Silverback\ApiComponentBundle\Entity\Content\FileInterface $object
     * @return array
     */
    private function getFileData(FileInterface $object): array
    {
        $data = [];
        $filePath = $object->getFilePath();
        if ($filePath) {
            if (false !== \exif_imagetype($filePath)) {
                [$width, $height] = getimagesize($filePath);
            } else {
                $width = $height = 0;
            }
            $data['width'] = $width;
            $data['height'] = $height;

            $supported = $this->isImagineSupportedFile($filePath);
            foreach ($object::getImagineFilters() as $returnKey => $filter) {
                $data[$returnKey] = $supported ? parse_url(
                    $this->imagineCacheManager->getBrowserPath($this->pathResolver->resolve($filePath), $filter),
                    PHP_URL_PATH
                ) : null;
            }
            $publicPaths = [$this->projectDir, '/public/', '/web/'];
            foreach ($publicPaths as $path) {
                if (mb_strpos($filePath, $path) === 0 && $start = \strlen($path)) {
                    $filePath = mb_substr($filePath, $start);
                }
            }
            $data['filePath'] = $filePath;
        }
        return $data;
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @return bool
     */
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $this->decorated->supportsDenormalization($data, $type, $format);
    }

    /**
     * @param mixed $data
     * @param string $class
     * @param string|null $format
     * @param array $context
     * @return object
     * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
     * @throws \Symfony\Component\Serializer\Exception\RuntimeException
     * @throws \Symfony\Component\Serializer\Exception\LogicException
     * @throws \Symfony\Component\Serializer\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Serializer\Exception\ExtraAttributesException
     * @throws \Symfony\Component\Serializer\Exception\BadMethodCallException
     * @throws \InvalidArgumentException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $context['allow_extra_attributes'] = $class === Form::class;
        return $this->decorated->denormalize($data, $class, $format, $context);
    }

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        if ($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }

    /**
     * @param string $filePath
     * @return bool
     */
    public function isImagineSupportedFile(?string $filePath): bool
    {
        if (!$filePath) {
            return false;
        }
        try {
            $imageType = \exif_imagetype($filePath);
        } catch (\Exception $e) {
            return false;
        }
        return \in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_JPEG2000, IMAGETYPE_PNG, IMAGETYPE_GIF], true);
    }
}
