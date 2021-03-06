<?php
/**
 * This file is part of the Networking package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Networking\InitCmsBundle\Controller;

use Networking\InitCmsBundle\Admin\Model\PageAdmin;
use Networking\InitCmsBundle\Component\EventDispatcher\CmsEventDispatcher;
use Networking\InitCmsBundle\Cache\PageCacheInterface;
use Networking\InitCmsBundle\Model\PageManagerInterface;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Class PageAdminController.
 *
 * @author net working AG <info@networking.ch>
 */
class LayoutBlockController extends CRUDController
{

    /**
     * @var bool
     */
    protected $error = false;

    /**
     * @var PageManagerInterface
     */
    protected $pageManager;

    /**
     * LayoutBlockController constructor.
     * @param CmsEventDispatcher $dispatcher
     * @param PageCacheInterface $pageCache
     * @param PageManagerInterface $pageManager
     */
    public function __construct(
        CmsEventDispatcher $dispatcher,
        PageCacheInterface $pageCache,
        PageManagerInterface $pageManager
    ) {
        $this->pageManager = $pageManager;
        
        parent::__construct($dispatcher, $pageCache);
    }

    /**
     * @return Response
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig_Error_Runtime
     */
    public function createAction()
    {
        if (!$this->isXmlHttpRequest()) {
            return new Response('cannot load external of page module', 403);
        }

        if (false === $this->admin->isGranted('CREATE')) {
            throw new AccessDeniedException();
        }

        $layoutBlock = $this->admin->getNewInstance();
        /** @var Request $request */
        $request = $this->getRequest();
        $this->admin->setSubject($layoutBlock);

        $formFieldId = $request->get('formFieldId');

        $code = $request->get('code');
        $pageId = $request->get('pageId');
        $uniqId = $request->get('uniqId');
        $classType = $request->get('classType');


        $pageAdmin = $this->container->get($code);
        $pageAdmin->setRequest($request);

        $page = $this->pageManager->find($pageId);
        if ($pageId && !$page) {
            throw new NotFoundHttpException();
        }

        if (!$page) {
            throw new NotFoundHttpException();
        }

        $request->attributes->add(['pageId' => $pageId]);
        $request->attributes->add(['page_locale' => $page->getLocale()]);

        $pageAdmin->setSubject($page);

        $layoutBlock->setZone($request->get('zone'));
        $layoutBlock->setSortOrder($request->get('sortOrder'));
        $layoutBlock->setClassType($classType);
        $layoutBlock->setPage($page);

        /** @var $form \Symfony\Component\Form\Form */
        $form = $this->admin->getForm();
        $form->setData($layoutBlock);
        $form->handleRequest($this->getRequest());
        if ($this->getRestMethod() == 'POST') {
            try {
                // persist if the form was valid and if in preview mode the preview was approved
                if ($form->isValid() && (!$this->isInPreviewMode() || $this->isPreviewApproved())) {
                    $this->admin->create($layoutBlock);
                    if ($this->isXmlHttpRequest()) {
                        return $this->renderJson(
                            [
                                'result' => 'ok',
                                'status' => 'success',
                                'message' => $this->translate('message.layout_block_created'),
                                'layoutBlockId' => $this->admin->getNormalizedIdentifier($layoutBlock),
                                'zone' => $layoutBlock->getZone(),
                                'sortOder' => $layoutBlock->getSortOrder(),
                                'html' => $this->getLayoutBlockFormWidget($pageId, $formFieldId, $uniqId, $code),
                            ]
                        );
                    }
                }

            } catch (ModelManagerException $e) {
                $formError = new FormError($e->getMessage());
                $form->addError($formError);
            }
        }

        $view = $form->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getRuntime(FormRenderer::class)->setTheme($view, $this->admin->getFormTheme());

        return $this->renderWithExtraParams(
            '@NetworkingInitCms/PageAdmin/layout_block_edit.html.twig',
            [
                'action' => 'create',
                'form' => $view,
                'object' => $layoutBlock,
            ]
        );
    }

    /**
     * @param null $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Twig_Error_Runtime
     */
    public function editAction($id = null)
    {
        $request = $this->getRequest();

        $pageId = $request->get('pageId');
        $formFieldId = $request->get('formFieldId');
        $uniqId = $request->get('uniqId');
        $code = $request->get('code', 'networking_init_cms.admin.page');


        $layoutBlock = $this->admin->getObject($id);

        /** @var $form \Symfony\Component\Form\Form */
        $form = $this->admin->getForm();
        $form->setData($layoutBlock);

        $form->handleRequest($request);
        $response = null;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->admin->update($layoutBlock);
                $html = $this->getLayoutBlockFormWidget($pageId, $formFieldId, $uniqId, $code);
                $status = 200;

                return new Response($html, $status);
            } else {
                $this->error = true;

                $response = new Response();
                $response->setStatusCode(500);
            }

        }

        $view = $form->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getRuntime(FormRenderer::class)->setTheme($view, $this->admin->getFormTheme());

        return $this->renderWithExtraParams(
            '@NetworkingInitCms/PageAdmin/layout_block_edit.html.twig',
            [
                'action' => 'create',
                'form' => $view,
                'object' => $layoutBlock,
            ],
            $response
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function reloadAction(Request $request)
    {
        $pageId = $request->get('pageId');
        $formFieldId = $request->get('formFieldId');
        $uniqId = $request->get('uniqId');
        $code = $request->get('code', 'networking_init_cms.admin.page');
        $html = $this->getLayoutBlockFormWidget($pageId, $formFieldId, $uniqId, $code);
        $status = 200;

        return new Response($html, $status);
    }

    /**
     * @param $pageId
     * @param $formFieldId
     * @param null $uniqId
     * @param string $code
     *
     * @return mixed
     *
     * @throws \Twig_Error_Runtime
     */
    protected function getLayoutBlockFormWidget(
        $pageId,
        $formFieldId,
        $uniqId,
        $code = 'networking_init_cms.admin.page'
    ) {
        if (!$formFieldId || !$code) {
            throw new NotFoundHttpException();
        }

        /** @var \Networking\InitCmsBundle\Admin\Model\PageAdmin $pageAdmin */
        $pageAdmin = $this->container->get($code);

        /** @var Request $request */
        $request = $this->getRequest();
        $pageAdmin->setRequest($request);
        $pageAdmin->setUniqid($uniqId);

        $page = $pageAdmin->getModelManager()->find($pageAdmin->getClass(), $pageId);
        if ($pageId && !$page) {
            throw new NotFoundHttpException();
        }

        if (!$page) {
            $page = $pageAdmin->getNewInstance();
        }

        $request->attributes->add(['objectId' => $pageId]);
        $request->attributes->add(['page_locale' => $page->getLocale()]);

        $pageAdmin->setSubject($page);
        $formBuilder = $pageAdmin->getFormBuilder();

        /** @var \Symfony\Component\Form\Form $form */
        $form = $formBuilder->getForm();
        $form->setData($page);

        /** @var \Sonata\AdminBundle\Admin\AdminHelper $helper */
        $helper = $this->get('sonata.admin.helper');
        $view = $helper->getChildFormView($form->createView(), $formFieldId);

        $twig = $this->get('twig');
        $twig->getRuntime(FormRenderer::class)->setTheme($view, $this->admin->getFormTheme());

        return $twig->getRuntime(FormRenderer::class)->searchAndRenderBlock($view, 'widget');
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateLayoutBlockSortAction(Request $request)
    {
        $zones = $request->get('zones', []);
        $pageId = $request->get('pageId');
        $code = $request->get('code');

        if (!$pageId) {
            throw $this->createNotFoundException();
        }
        $pageAdmin = $this->container->get($code);
        foreach ($zones as $zone) {
            $zoneName = $zone['zone'];
            if (array_key_exists('layoutBlocks', $zone) && is_array($zone['layoutBlocks'])) {
                foreach ($zone['layoutBlocks'] as $key => $layoutBlockStr) {
                    $sort = ++$key;
                    $blockId = str_replace('layoutBlock_', '', $layoutBlockStr);

                    if ($blockId) {
                        try {
                            /** @var \Networking\InitCmsBundle\Model\LayoutBlockInterface $layoutBlock */
                            $layoutBlock = $this->admin->getObject($blockId);
                            if ($layoutBlock) {
                                $layoutBlock->setSortOrder($sort);
                                $layoutBlock->setZone($zoneName);
                            }

                            $this->admin->update($layoutBlock);
                        } catch (\Exception $e) {
                            $message = $e->getMessage();

                            return new JsonResponse(['messageStatus' => 'error', 'message' => $message]);
                        }
                    }
                }
            }
        }


        $page = $pageAdmin->getModelManager()->find($pageAdmin->getClass(), $pageId);
        $pageAdmin->setSubject($page);

        $pageStatus = $this->renderView(
            '@NetworkingInitCms/PageAdmin/page_status_settings.html.twig',
            ['admin' => $pageAdmin, 'object' => $page]
        );

        $data = [
            'pageStatusSettings' => $pageStatus,
            'pageStatus' => $this->translate($page->getStatus()),
            'messageStatus' => 'success',
            'message' => $this->translate('message.layout_blocks_sorted', ['zone' => '']),
        ];

        return new JsonResponse($data);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Twig_Error_Runtime
     */
    public function deleteAjaxAction(Request $request)
    {
        $id = $request->get('id');
        $pageId = $request->get('pageId');
        $uniqId = $request->get('uniqId');
        $code = $request->get('code');
        $formFieldId = $request->get('formFieldId');

        if ($id) {
            $layoutBlock = $this->admin->getObject($id);
            if ($layoutBlock) {
                $this->admin->delete($layoutBlock);
            }
        }
        $html = $this->getLayoutBlockFormWidget($pageId, $formFieldId, $uniqId, $code);

        return new JsonResponse(
            [
                'messageStatus' => 'success',
                'message' => $this->translate('message.layout_block_deleted'),
                'html' => $html,
            ]
        );
    }
}
