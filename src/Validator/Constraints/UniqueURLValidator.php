<?php
/**
 * This file is part of the Networking package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Networking\InitCmsBundle\Validator\Constraints;

use Gedmo\Sluggable\Util\Urlizer;
use Networking\InitCmsBundle\Model\PageManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Doctrine\ORM\EntityManager;

/**
 * Class UniqueURLValidator.
 *
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class UniqueURLValidator extends ConstraintValidator
{
    /**
     * @var EntityManager
     */
    protected $om;

    /**
     * @var PageManagerInterface
     */
    protected $pageManager;

    /**
     * @param PageManagerInterface $pageManager
     */
    public function __construct(PageManagerInterface $pageManager)
    {
        $this->pageManager = $pageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $url = Urlizer::urlize($value->getUrl());
        $pages = $this->pageManager->findBy(['url' => $url, 'parent' => $value->getParent(), 'locale' => $value->getLocale()]);

        if ($value->getParent()) {
            $url = $value->getParent()->getFullPath().$url;
        }
        if (count($pages) > 0) {
            foreach ($pages as $page) {
                /** @var \Networking\InitCmsBundle\Model\PageInterface $page */
                if ($page->getId() != $value->getId()) {
                    $this->context->addViolationAt('url', $constraint->message, ['{{ value }}' => $url]);

                    return false;
                }
            }
        }

        return true;
    }
}
