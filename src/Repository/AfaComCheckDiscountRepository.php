<?php

namespace App\Repository;

use App\Entity\AfaComCheckDiscount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AfaComCheckDiscount|null find($id, $lockMode = null, $lockVersion = null)
 * @method AfaComCheckDiscount|null findOneBy(array $criteria, array $orderBy = null)
 * @method AfaComCheckDiscount[]    findAll()
 * @method AfaComCheckDiscount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AfaComCheckDiscountRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AfaComCheckDiscount::class);
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
