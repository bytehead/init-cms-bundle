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

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class HelpTextController
 * @package Networking\InitCmsBundle\Controller
 *
 * @author net working AG <info@networking.ch>
 */
class HelpTextController extends Controller
{


    /**
     * Help text page action
     *
     * @param Request $request
     * @param $adminCode
     * @param string $action
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function adminHelpAction(Request $request, $adminCode, $action = '')
    {
        $parameters = ['adminCode' => $adminCode, 'action' => $action];
        $defaultAdminCode = ['dashboard', 'overview'];

        if ($action == '') {
            $translationKey = $adminCode;
        } else {
            $translationKey = $adminCode . '.' . $action;
        }

        $helpTextManager = $this->get('networking_init_cms.help_text_manager');
        $helpText = $helpTextManager->getHelpTextByKeyLocale($translationKey, $request->getLocale());

        $parameters['help_text'] = $helpText;
        if (!in_array($adminCode, $defaultAdminCode)) {

            $admin = $this->container->get('sonata.admin.pool')->getAdminByAdminCode($adminCode);
            $admin->setRequest($request);
            $parameters['admin'] = $admin;

        }

        /** @var \Networking\InitCmsBundle\Admin\Pool $pool */
        $pool = $this->get('sonata.admin.pool');
        $parameters['admin_pool'] = $pool;
        $parameters['base_template'] = isset($admin) ?  $this->getBaseTemplate($request, $admin): '@NetworkingInitCms/admin_layout.html.twig';
        $dashBoardGroups = $pool->getDashboardNavigationGroups();


        $parameters['help_nav'] = $this->adminGetHelpTextNavigation(
            $dashBoardGroups,
            $request->getLocale(),
            $helpTextManager
        );

        return $this->render(
            '@NetworkingInitCms/HelpText/adminHelp.html.twig',
            $parameters
        );
    }

    /**
     * return the base template name
     *
     * @param Request $request
     * @param AbstractAdmin $admin
     * @return string the template name
     */
    protected function getBaseTemplate(Request $request, AbstractAdmin $admin)
    {
        if ($request->isXmlHttpRequest()) {
            return $admin->getTemplate('ajax');
        }

        return $admin->getTemplate('layout');
    }

    /**
     * Create the navigation for the help text view
     *
     * @param array $dashBoardGroups
     * @param $locale
     * @param $helpTextManager
     * @return array
     */
    protected function adminGetHelpTextNavigation(array $dashBoardGroups, $locale, $helpTextManager)
    {
        $navArray = [];

        //add overview & dashboard manually
        $navArray['overview']['group_name'] = $this->get('translator')->trans(
            'title.help',
            [],
            'HelpTextAdmin'
        );;
        $navArray['overview']['group_items']['0']['adminCode'] = 'overview';
        $navArray['overview']['group_items']['0']['action'] = '';
        $navArray['overview']['group_items']['0']['title'] = $this->get('translator')->trans(
            'overview.title',
            [],
            'HelpTextAdmin'
        );

        $navArray['dashboard']['group_name'] = 'Dashboard';
        $navArray['dashboard']['group_items']['0']['adminCode'] = 'dashboard';
        $navArray['dashboard']['group_items']['0']['action'] = '';
        $navArray['dashboard']['group_items']['0']['title'] = $this->get('translator')->trans(
            'dashboard.title',
            [],
            'HelpTextAdmin'
        );

        foreach ($dashBoardGroups as $key => $group) {
            foreach ($group['sub_group'] as $subGroup) {


                $navArray[$subGroup['label']]['group_items'] = [];
                $i = 0;
                foreach ($subGroup['items'] as $admin) {
                    if (0 == $i++) {
                        $navArray[$subGroup['label']]['group_name'] = $this->get('translator')->trans(
                            $subGroup['label'],
                            [],
                            $admin->getTranslationDomain()
                        );
                    }

                    $help_text_result = $helpTextManager->searchHelpTextByKeyLocale($admin->getCode(), $locale);
                    if (count($help_text_result) > 0) {

                        foreach ($help_text_result as $row) {
                            //split Translation Key into adminCode and action
                            $strripos = strripos($row->getTranslationKey(), '.');
                            $action = substr($row->getTranslationKey(), $strripos + 1);
                            $navArray[$subGroup['label']]['group_items'][$row->getId()]['adminCode'] = $admin->getCode(
                            );
                            $navArray[$subGroup['label']]['group_items'][$row->getId()]['action'] = $action;
                            $navArray[$subGroup['label']]['group_items'][$row->getId()]['title'] = $row->getTitle();
                        }
                    }
                }
            }
        }

        return $navArray;
    }
}
