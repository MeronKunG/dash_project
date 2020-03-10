<?php

namespace App\Repository;

use App\Entity\HdComCheckPriceCom;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method HdComCheckPriceCom|null find($id, $lockMode = null, $lockVersion = null)
 * @method HdComCheckPriceCom|null findOneBy(array $criteria, array $orderBy = null)
 * @method HdComCheckPriceCom[]    findAll()
 * @method HdComCheckPriceCom[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HdComCheckPriceComRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, HdComCheckPriceCom::class);
    }

    public function getAllDataComCheck($userId, $initialRef)
    {
        return $this->createQueryBuilder('c')
            ->select('c.paymentInvoice, c.productName, c.productQty, c.totalCom, c.adjustedPrice')
            ->where('c.adminId = :adminId AND c.initialRef = :initialRef')
            ->setParameter('adminId', $userId)
            ->setParameter('initialRef', $initialRef)
            ->getQuery()
            ->getResult();
    }
}
