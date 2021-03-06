<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;

class EmailRepository extends EntityRepository
{
    /**
     * Gets emails by ids
     *
     * @param array $ids
     *
     * @return array
     */
    public function findEmailsByIds($ids)
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $criteria     = new Criteria();
        $criteria->where(Criteria::expr()->in('id', $ids));
        $criteria->orderBy(['sentAt' => Criteria::DESC]);
        $queryBuilder->addCriteria($criteria);
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    /**
     * Gets email by Message-ID
     *
     * @param string $messageId
     *
     * @return Email|null
     */
    public function findEmailByMessageId($messageId)
    {
        return $this->createQueryBuilder('e')
            ->where('e.messageId = :messageId')
            ->setParameter('messageId', $messageId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get $limit last emails
     *
     * @param User $user
     * @param $limit
     *
     * @return mixed
     */
    public function getNewEmails(User $user, $limit)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e, eu.seen')
            ->leftJoin('e.emailUsers', 'eu')
            ->where('eu.organization = :organizationId')
            ->andWhere('eu.owner = :ownerId')
            ->groupBy('e, eu.seen')
            ->orderBy('e.sentAt', 'DESC')
            ->setParameter('organizationId', $user->getOrganization()->getId())
            ->setParameter('ownerId', $user->getId())
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get count new emails
     *
     * @param User $user
     *
     * @return mixed
     */
    public function getCountNewEmails(User $user)
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e)')
            ->leftJoin('e.emailUsers', 'eu')
            ->where('eu.organization = :organizationId')
            ->andWhere('eu.owner = :ownerId')
            ->andWhere('eu.seen = :seen')
            ->setParameter('organizationId', $user->getOrganization()->getId())
            ->setParameter('ownerId', $user->getId())
            ->setParameter('seen', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
