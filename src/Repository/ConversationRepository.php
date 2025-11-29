<?php

namespace App\Repository;

use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    //    /**
    //     * @return Conversation[] Returns an array of Conversation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    /**
     * Récupère les conversations où l'utilisateur est userA, triées par dernier message
     */
    public function findConversationsAsUserAOrderedByLastMessage($user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.messages', 'm')
            ->andWhere('c.userA = :user')
            ->setParameter('user', $user)
            ->addSelect('MAX(m.createdAt) as HIDDEN max_date')
            ->groupBy('c.id')
            ->orderBy('max_date', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les conversations où l'utilisateur est userB, triées par dernier message
     */
    public function findConversationsAsUserBOrderedByLastMessage($user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.messages', 'm')
            ->andWhere('c.userB = :user')
            ->setParameter('user', $user)
            ->addSelect('MAX(m.createdAt) as HIDDEN max_date')
            ->groupBy('c.id')
            ->orderBy('max_date', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère toutes les conversations d'un utilisateur triées par dernier message
     */
    public function findAllUserConversationsOrderedByLastMessage($user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.messages', 'm')
            ->andWhere('c.userA = :user OR c.userB = :user')
            ->setParameter('user', $user)
            ->addSelect('MAX(m.createdAt) as HIDDEN max_date')
            ->groupBy('c.id')
            ->orderBy('max_date', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
