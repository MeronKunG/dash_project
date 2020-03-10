<?php

namespace App\Repository;

use App\Entity\HdComCheckDiscount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method HdComCheckDiscount|null find($id, $lockMode = null, $lockVersion = null)
 * @method HdComCheckDiscount|null findOneBy(array $criteria, array $orderBy = null)
 * @method HdComCheckDiscount[]    findAll()
 * @method HdComCheckDiscount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HdComCheckDiscountRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, HdComCheckDiscount::class);
    }

    public function getAllDataDiscountCheck($userId, $initialRef)
    {
        return $this->createQueryBuilder('c')
            ->select('c.paymentInvoice, c.discount')
            ->where('c.adminId = :adminId AND c.initialRef = :initialRef')
            ->setParameter('adminId', $userId)
            ->setParameter('initialRef', $initialRef)
            ->getQuery()
            ->getResult();
    }
}
