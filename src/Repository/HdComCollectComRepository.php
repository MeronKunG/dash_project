<?php

namespace App\Repository;

use App\Entity\HdComCollectCom;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method HdComCollectCom|null find($id, $lockMode = null, $lockVersion = null)
 * @method HdComCollectCom|null findOneBy(array $criteria, array $orderBy = null)
 * @method HdComCollectCom[]    findAll()
 * @method HdComCollectCom[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HdComCollectComRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, HdComCollectCom::class);
    }

    public function getAllDataComCheck($userId, $initialRef)
    {
        return $this->createQueryBuilder('aa')
            ->select('aa.paymentInvoice, bb.productname AS productName, aa.productQty, aa.totalCom, aa.adjustedPrice, dd.orderdate, ee.sendmaildate, ee.transactiondate')
            ->leftJoin('App\Entity\GlobalProduct', 'bb', 'WITH', 'aa.productId = bb.productid')
            ->leftJoin('App\Entity\GlobalAuthen', 'cc', 'WITH', 'aa.adminId = cc.id')
            ->leftJoin('App\Entity\MerchantBilling', 'dd', 'WITH', 'aa.takeorderby = dd.takeorderby AND aa.paymentInvoice = dd.paymentInvoice')
            ->leftJoin('App\Entity\MerchantBillingDelivery', 'ee', 'WITH', 'aa.takeorderby = ee.takeorderby AND aa.paymentInvoice = ee.paymentInvoice')
            ->where('aa.adminId = :adminId AND aa.initialRef = :initialRef')
            ->setParameter('adminId', $userId)
            ->setParameter('initialRef', $initialRef)
            ->getQuery()
            ->getResult();
    }
}
