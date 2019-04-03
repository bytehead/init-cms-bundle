<?php
/**
 * This file is part of the Networking package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Networking\InitCmsBundle\Admin\Model;

use JMS\Serializer\Serializer;
use Networking\InitCmsBundle\Model\LayoutBlock;
use Networking\InitCmsBundle\Model\PageInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Form\FormMapper;
use Networking\InitCmsBundle\Form\DataTransformer\PageToIdTransformer;
use Networking\InitCmsBundle\Admin\BaseAdmin;
use Networking\InitCmsBundle\Model\ContentInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class LayoutBlockAdmin.
 *
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
abstract class LayoutBlockAdmin extends BaseAdmin
{
    /**
     * @var string
     */
    protected $baseRoutePattern = 'cms/layout_block';
    /**
     * used to prefix dynamically generated form fields.
     */
    const CUSTOM_FIELD_PREFIX = 'networking_init_cms_content_';

    public $trackedActions = [];

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('deleteAjax', 'delete_ajax', [], ['method' => 'POST']);
        $collection->add('reload', 'reload', [], ['method' => 'GET']);
        $collection->add('updateFormFieldElement', 'update_form_fields', [], ['method' => 'POST']);
        $collection->add('updateLayoutBlockSort', 'update_layout_block_sort', [], ['method' => 'GET']);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        if ($this->getSubject()) {
            $classType = $this->getSubject()->getClassType();
        } else {
            $classType = $this->getRequest()->get('classType');
        }
        /** @var \Networking\InitCmsBundle\Model\LayoutBlockFormListener $listener */
        $listener = $this->getContainer()->get('networking_init_cms.layout_block_form_listener');
        $listener->setAdmin($this);
        $listener->setContentType($classType);
        $formMapper->getFormBuilder()->addEventSubscriber($listener);

        $pageManager = $this->getContainer()->get('networking_init_cms.page_manager');
        $transformer = new PageToIdTransformer($pageManager);

        $formMapper
            ->add(
                'zone',
                HiddenType::class
            )
            ->add(
                $formMapper->getFormBuilder()->create('page', HiddenType::class)
                    ->addModelTransformer($transformer),
                HiddenType::class
            )
            ->add('classType', HiddenType::class)
            ->add('sortOrder', HiddenType::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add('page');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
            ->add('page')
            ->add('isActive');
    }

    /**
     * @return array
     */
    public function getContentTypes()
    {
        $contentTypes = $this->getContainer()->getParameter('networking_init_cms.page.content_types');

        $choices = [];
        foreach ($contentTypes as $contentType) {
            $choices[$contentType['class']] = $contentType['name'];
        }

        return $choices;
    }

    /**
     * @param mixed $object
     *
     * @return mixed|void
     *
     * @throws ModelManagerException
     */
    public function prePersist($object)
    {
        /** @var ContentInterface $contentObject */
        if ($contentObject = $object->getContent()) {
            try {
                $this->getModelManager()->create($contentObject);
                $object->setObjectId($contentObject->getId());
                $this->autoPageDraft($object->getPage());
            } catch (ModelManagerException $e) {
                throw new ModelManagerException($this->trans('Cannot create content, object is invalid', [], 'validators'));
            }
        }
    }

    /**
     * @param LayoutBlock $object
     *
     * @return mixed|void
     */
    public function preUpdate($object)
    {
        if ($contentObject = $object->getContent()) {
            $this->getModelManager()->update($contentObject);
        }
        $this->autoPageDraft($object->getPage());
    }

    /**
     * @param LayoutBlock $object
     *
     * @return mixed|void
     */
    public function preRemove($object)
    {
        if ($classType = $object->getClassType()) {
            $contentObject = $this->getModelManager()->find($classType, $object->getObjectId());
            if ($contentObject) {
                $this->getModelManager()->delete($contentObject);
            }
        }

        $this->autoPageDraft($object->getPage());
    }

    /**
     * @return Serializer
     */
    protected function getSerializer()
    {
        return $this->getContainer()->get('jms_serializer');
    }

    /**
     * @param PageInterface $page
     */
    public function autoPageDraft(PageInterface $page)
    {
        $page->setStatus(PageInterface::STATUS_DRAFT);
        $page->setUpdatedAt(new \DateTime());
        /** @var PageAdmin $pageAdmin */
        $pageAdmin = $this->getContainer()->get('networking_init_cms.admin.page');
        $pageAdmin->update($page);
    }
}
