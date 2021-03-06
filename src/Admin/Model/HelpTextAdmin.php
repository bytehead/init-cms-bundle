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

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Networking\InitCmsBundle\Admin\BaseAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class HelpTextAdmin.
 *
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
abstract class HelpTextAdmin extends BaseAdmin
{
    /**
     * @var string
     */
    protected $baseRoutePattern = 'cms/help';

    /**
     * @return string
     */
    public function getIcon()
    {
        return 'glyphicon-question';
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('title')
            ->addIdentifier('translationKey')
            ->add('locale')
            ->add(
                '_action',
                'actions',
                [
                    'label' => false,
                    'actions' => [
                        'edit' => [],
                        'delete' => [],
                    ],
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add(
                'locale',
                ChoiceType::class,
                [
                    'choice_loader' => new CallbackChoiceLoader(function () {
                        return $this->getLocaleChoices();
                    }),
                    'preferred_choices' => [$this->getDefaultLocale()],
                    'translation_domain' => false,
                ]
            )
            ->add('translationKey')
            ->add('title', null, ['required' => true])
            ->add(
                'text',
                CKEditorType::class,
                ['config' => ['toolbar' => 'standard', 'contentsCss' => null]]
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add(
                'locale',
                CallbackFilter::class,
                [
                    'callback' => [
                        $this,
                        'getByLocale',
                    ],
                    'hidden' => false,
                ],
                ChoiceType::class,
                [
                    'placeholder' => false,
                    'choice_loader' => new CallbackChoiceLoader(function () {
                        return $this->getLocaleChoices();
                    }),
                    'preferred_choices' => [$this->getDefaultLocale()],
                    'translation_domain' => false,
                ]

            );
    }

    /**
     * @param array $filterValues
     */
    public function configureDefaultFilterValues(array &$filterValues)
    {
        $filterValues['locale'] = [
            'type' => \Sonata\AdminBundle\Form\Type\Filter\ChoiceType::TYPE_EQUAL,
            'value' => $this->getDefaultLocale(),
        ];
    }

    /**
     * @param ProxyQuery $ProxyQuery
     * @param $alias
     * @param $field
     * @param $data
     *
     * @return bool
     */
    public function getByLocale(ProxyQuery $ProxyQuery, $alias, $field, $data)
    {
        $active = true;
        if (!$locale = $data['value']) {
            $locale = $this->getDefaultLocale();
            $active = false;
        }

        $qb = $ProxyQuery->getQueryBuilder();

        $qb->andWhere(sprintf('%s.%s = :locale', $alias, $field));
        $qb->orderBy(sprintf('%s.translationKey', $alias), 'asc');
        $qb->setParameter(':locale', $locale);

        return $active;
    }
}
