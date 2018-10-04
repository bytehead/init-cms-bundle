<?php
/**
 * This file is part of the Networking package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Networking\InitCmsBundle\Menu;

use Networking\InitCmsBundle\Entity\MenuItem as Menu;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class FrontendMenuBuilder.
 *
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class FrontendMenuBuilder extends MenuBuilder
{
    /**
     * Creates the main page navigation for the left side of the top frontend navigation.
     *
     * @param $menuName
     * @param string $classes
     *
     * @return \Knp\Menu\ItemInterface|\Knp\Menu\MenuItem
     */
    public function createMainMenu($menuName, $classes = 'nav nav-tabs nav-stacked')
    {
        $menu = $this->factory->createItem('root');

        if ($classes) {
            $menu->setChildrenAttribute('class', $classes);
        }

        /** @var $mainMenu Menu */
        $menuIterator = $this->getFullMenu($menuName);

        if (!$menuIterator) {
            return $menu;
        }

        $startDepth = 1;
        $menu = $this->createMenu($menu, $menuIterator, $startDepth);

        return $menu;
    }

    /**
     * Create frontend sub navigation on the left hand side of the screen.
     *
     * @param string $menuName
     * @param string $classes
     *
     * @return bool|\Knp\Menu\ItemInterface
     */
    public function createSubnavMenu($menuName, $classes = 'nav nav-tabs nav-stacked')
    {
        $menu = $this->factory->createItem('root');

        if ($classes) {
            $menu->setChildrenAttribute('class', $classes);
        }

        /** @var $mainMenu Menu */
        $menuIterator = $this->getSubMenu($menuName, 1);

        if (!$menuIterator) {
            return $menu;
        }

        $startDepth = 2;

        $menu = $this->createMenu($menu, $menuIterator, $startDepth);
        $this->showOnlyCurrentChildren($menu);
        $this->setRecursiveAttribute($menu, ['class' => 'nav nav-list']);

        return $menu;
    }

    /**
     * Creates the login and change language navigation for the right side of the top frontend navigation.
     *
     * @param RequestStack $requestStack
     * @param $languages
     * @param string $classes
     * @param bool   $dropDownMenu
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function createFrontendLangMenu(
        RequestStack $requestStack,
        $languages,
        $classes = 'nav pull-right',
        $dropDownMenu = false
    ) {
        $menu = $this->factory->createItem('root');
        if ($classes) {
            $menu->setChildrenAttribute('class', $classes);
        }

        if ($dropDownMenu) {
            $this->createDropdownLangMenu($menu, $languages, $requestStack->getCurrentRequest()->getLocale());
        } else {
            $this->createInlineLangMenu($menu, $languages, $requestStack->getCurrentRequest()->getLocale());
        }

        return $menu;
    }

    /**
     * Used to create a simple navigation for the footer.
     *
     * @param $menuName
     * @param string $classes
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function createFooterMenu($menuName, $classes = '')
    {
        $menu = $this->factory->createItem($menuName);
        if ($classes) {
            $menu->setChildrenAttribute('class', $classes);
        }

        /** @var $mainMenu Menu */
        $menuIterator = $this->getFullMenu($menuName);

        if (!$menuIterator) {
            return $menu;
        }

        $startDepth = 1;
        $menu = $this->createMenu($menu, $menuIterator, $startDepth);

        return $menu;
    }

    /**
     * Used to create nodes for the language navigation in the front- and backend.
     *
     * @param \Knp\Menu\ItemInterface $menu
     * @param array                   $languages
     * @param $currentLanguage
     * @param string $route
     */
    public function createDropdownLangMenu(
        \Knp\Menu\ItemInterface &$menu,
        array $languages,
        $currentLanguage,
        $route = 'networking_init_change_language'
    ) {
        $dropdown = $menu->addChild(
            $this->translator->trans('Change Language'),
            ['dropdown' => true, 'icon' => 'caret']
        );

        foreach ($languages as $language) {
            $node = $dropdown->addChild(
                $language['label'],
                ['uri' => $this->router->generate($route, ['oldLocale' => $this->request->getLocale(), 'locale' => $language['locale']])]
            );

            if ($language['locale'] == $currentLanguage) {
                $node->setCurrent(true);
            }
            $node->setExtra('translation_domain', false);
        }
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     * @param array                   $languages
     * @param $currentLanguage
     * @param string $route
     */
    public function createInlineLangMenu(
        \Knp\Menu\ItemInterface &$menu,
        array $languages,
        $currentLanguage,
        $route = 'networking_init_change_language'
    ) {
        foreach ($languages as $language) {
            $node = $menu->addChild(
                $language['label'],
                [
                    'uri' => $this->router->generate($route, ['oldLocale' => $this->request->getLocale(), 'locale' => $language['locale']]),
                    'linkAttributes' => ['class' => 'language'],
                ]
            );
            if ($language['locale'] == $currentLanguage) {
                $node->setCurrent(true);
            }
            $node->setExtra('translation_domain', false);
        }
    }
}
