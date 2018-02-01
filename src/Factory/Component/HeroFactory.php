<?php

namespace Silverback\ApiComponentBundle\Factory\Component;

use Silverback\ApiComponentBundle\Entity\Component\AbstractComponent;
use Silverback\ApiComponentBundle\Entity\Component\Hero\Hero;
use Silverback\ApiComponentBundle\Entity\Content\AbstractContent;

class HeroFactory extends AbstractComponentFactory
{
    public function getComponent(): AbstractComponent
    {
        return new Hero();
    }

    public static function defaultOps(): array
    {
        return array_merge(parent::defaultOps(), [
            'title' => 'No Title Set',
            'subtitle' => null
        ]);
    }

    public function create(AbstractContent $owner, array $ops = null): AbstractComponent
    {
        /**
         * @var Hero $component
         */
        $ops = self::processOps($ops);
        $component = parent::create($owner, $ops);
        $component->setTitle($ops['title']);
        $component->setSubtitle($ops['subtitle']);
        return $component;
    }
}
