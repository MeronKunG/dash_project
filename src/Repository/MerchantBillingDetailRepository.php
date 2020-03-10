<?php

namespace App\Repository;

use App\Entity\MerchantBillingDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MerchantBillingDetail|null find($id, $lockMode = null, $lockVersion = null)
 * @method MerchantBillingDetail|null findOneBy(array $criteria, array $orderBy = null)
 * @method MerchantBillingDetail[]    findAll()
 * @method MerchantBillingDetail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MerchantBillingDetailRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MerchantBillingDetail::class);
    }

    public function getProductAndCommissionByTakeOrderBy($takeorderby, $paymentInvoice)
    {
        return $this->createQueryBuilder('mbd')
            ->select('mbd.productname, mbd.productorder, mbd.productCommission, mbd.afaCommissionValue, mbd.afaCommissionBonus')
            ->where('mbd.takeorderby = :takeorderby AND mbd.paymentInvoice = :paymentInvoice')
            ->setParameter('takeorderby', $takeorderby)
            ->setParameter('paymentInvoice', $paymentInvoice)
            ->getQuery()
            ->getResult();
    }

    public function getProductAndOrder($adminId, $takeorderby, $startDate, $lastDate)
    {
        return $this->createQueryBuilder('mbd')
            ->select('mbd.productname, SUM(mbd.productorder) AS amounts, CONCAT(\'https://www.945holding.com\', mbd.imgproductpathonbill, \'/\', mbd.imgproductonbill) AS path, cast(mb.orderdate as date) AS dateOnly')
            ->leftJoin('App\Entity\MerchantBilling', ' mb', 'WITH', 'mbd.takeorderby = mb.takeorderby AND mbd.paymentInvoice = mb.paymentInvoice')
            ->where('mbd.takeorderby = :takeorderby AND mb.adminid = :adminId')
            ->andWhere('DATE(mb.orderdate) BETWEEN :startDate AND :lastDate')
            ->andWhere('mb.orderstatus <> \'101\'')
            ->setParameter('adminId', $adminId)
            ->setParameter('takeorderby', $takeorderby)
            ->setParameter('startDate', $startDate)
            ->setParameter('lastDate', $lastDate)
            ->groupBy('mbd.globalProductid, dateOnly')
            ->getQuery()
            ->getResult();
    }
}
