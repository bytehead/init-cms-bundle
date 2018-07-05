<?php
/**
 * This file is part of the init_cms_sandbox package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Networking\InitCmsBundle\Model;

use Networking\InitCmsBundle\Serializer\PageSnapshotDeserializationContext;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use Networking\InitCmsBundle\Model\PageSnapshotManagerInterface;

/**
 * Class PageListener
 * @package Networking\InitCmsBundle\Model
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
abstract class PageListener implements EventSubscriberInterface, PageListenerInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var PageManagerInterface
     */
    protected $pageManager;

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return PageManagerInterface|object
     */
    public function getPageManager()
    {
        if (!$this->pageManager) {
            $this->pageManager = $this->container->get('networking_init_cms.page_manager');
        }

        return $this->pageManager;
    }

    /**
     * @return PageSnapshotManagerInterface
     */
    public function getPageSnapshotManager()
    {
        return $this->container->get('networking_init_cms.page_snapshot_manager');

    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => 'serializer.post_deserialize',
                'method' => 'onPostDeserialize',
                'format' => 'json'
            ],
        ];
    }

    /**
     * @param \JMS\Serializer\EventDispatcher\ObjectEvent $event
     */
    public function onPostDeserialize(\JMS\Serializer\EventDispatcher\ObjectEvent $event)
    {
        /** @var $page PageInterface */
        $page = $event->getObject();

        if ($page instanceof PageInterface) {
            $context = $event->getContext();

            $er = $this->getPageManager();
            if(!$page->getId()){
                return;
            }

            if ($parent = $page->getParent()) {

                $parent = $er->find($page->getParent());
                $page->setParent($parent);
            } else {
                $page->setParent(null);
            }
            
            if ($alias = $page->getAlias()) {
                $alias = $er->find($page->getAlias());
                $page->setAlias($alias);
            } else {
                $page->setAlias(null);
            }

            if ($parents = $page->getParents()) {
                foreach ($parents as $key => $parent) {
                    if(is_array($parent) && array_key_exists('id', $parent)){
                        $parent = $parent['id'];
                    }
                    $parents[$key] = $er->find($parent);
                }

                $page->setParents($parents);
            } else {
                $page->setParents([]);
            }

            if ($children = $page->getChildren()) {
                foreach ($children as $key => $child) {
                    $children[$key] = $er->find($child);
                }

                $page->setChildren($children);
            } else {
                $page->setChildren([]);
            }

            if ($originals = $page->getOriginals()) {
                foreach ($originals as $key => $original) {
                    $originals[$key] = $er->find($original);
                }

                $page->setOriginals($originals);
            } else {
                $page->setOriginals([]);
            }
            if ($context instanceof PageSnapshotDeserializationContext &&  $context->deserializeTranslations()) {
                if ($translations = $page->getTranslations()) {
                    foreach ($translations as $key => $translation) {
                        $translations[$key] = $er->find($translation);
                    }
                    $page->setTranslations($translations);
                } else {
                    $originalPageId = $page->getId();
                    $originalPage = $er->find($originalPageId);
                    $page->setTranslations($originalPage->getAllTranslations()->toArray());
                }
            }

            if (!$contentRoute = $page->getContentRoute()->getId()) {

                $pageSnapshotManager = $this->getPageSnapshotManager();
                $lastPageSnapshot = $pageSnapshotManager->findLastPageSnapshot($page->getId());

                if($lastPageSnapshot){
                    $page->setContentRoute($lastPageSnapshot->getContentRoute());
                }
            }
        }
    }

}
 