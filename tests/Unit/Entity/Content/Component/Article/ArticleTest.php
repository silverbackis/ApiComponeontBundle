<?php

namespace Silverback\ApiComponentBundle\Tests\Unit\Entity\Content\Component\Article;

use Silverback\ApiComponentBundle\Entity\Content\Component\Article\Article;
use Silverback\ApiComponentBundle\Tests\Unit\Entity\AbstractEntity;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotNull;

class ArticleTest extends AbstractEntity
{
    public function test_constraints()
    {
        $entity = new Article();
        $constraints = $this->getConstraints($entity);
        $this->assertTrue($this->instanceInArray(Image::class, $constraints['filePath']));
        $this->assertTrue($this->instanceInArray(NotNull::class, $constraints['title']));
        $this->assertTrue($this->instanceInArray(NotNull::class, $constraints['content']));
    }
}