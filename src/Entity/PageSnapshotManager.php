<?php
/**
 * This file is part of the Networking package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Networking\InitCmsBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Networking\InitCmsBundle\Model\PageSnapshotManagerInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class PageSnapshotManager
 * @package Networking\InitCmsBundle\Entity
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class PageSnapshotManager extends EntityRepository implements PageSnapshotManagerInterface
{

    /**
     * @param EntityManager $em
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     */
    public function __construct(EntityManager $em, $class)
    {
        $this->manager = $em;
        $classMetaData = $em->getClassMetadata($class);

        parent::__construct($em, $classMetaData);
    }

    /**
     * @param $pageId
     * @return mixed
     */
    public function findSnapshotByPageId($pageId)
    {
        $qb = $this->createQueryBuilder('ps')
            ->where('ps.page = :pageId')
            ->orderBy('ps.version', 'desc')
            ->setParameter(':pageId', $pageId);

        return $qb->getQuery()->execute();
    }

    /**
     * @param $pageId
     * @return mixed
     */
    public function findLastPageSnapshot($pageId)
    {
        $qb = $this->createQueryBuilder('ps')
            ->where('ps.page = :pageId')
            ->orderBy('ps.version', 'desc')
            ->setMaxResults(1)
            ->setParameter(':pageId', 2);

        try{
            return $qb->getQuery()->getSingleResult();
        }catch (NoResultException $e){
            return null;
        }catch (NonUniqueResultException $e){
            return null;
        }
    }


}