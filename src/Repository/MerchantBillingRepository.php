<?php

namespace App\Repository;

use App\Entity\MerchantBilling;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MerchantBilling|null find($id, $lockMode = null, $lockVersion = null)
 * @method MerchantBilling|null findOneBy(array $criteria, array $orderBy = null)
 * @method MerchantBilling[]    findAll()
 * @method MerchantBilling[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MerchantBillingRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MerchantBilling::class);
    }

    public function countInvoice($takeorderby, $adminId)
    {
        return $this->createQueryBuilder('mb')
            ->select('mb.orderstatus')
            ->leftJoin('App\Entity\MerchantBillingDelivery', 'mbdy', 'WITH',
                'mb.takeorderby = mbdy.takeorderby AND mb.paymentInvoice = mbdy.paymentInvoice')
            ->where('mb.takeorderby = :takeorderby AND mb.adminid = :adminId')
            ->setParameter('takeorderby', $takeorderby)
            ->setParameter('adminId', $adminId)
            ->getQuery()
            ->getResult();
    }

    public function getDateByInvoice($takeorderby, $adminId)
    {
        return $this->createQueryBuilder('mb')
            ->select('mb.orderdate, hour(mb.orderdate) AS hour, mb.paymentAmt, mb.paymentDiscount, mb.transportprice, mb.orderstatus')
            ->where('mb.takeorderby = :takeorderby AND mb.adminid = :adminId')
            ->setParameter('takeorderby', $takeorderby)
            ->setParameter('adminId', $adminId)
            ->getQuery()
            ->getResult();
    }

    public function getDataByInvoice($takeorderby, $adminId)
    {
        return $this->createQueryBuilder('mb')
            ->select('mb.orderdate, hour(mb.orderdate) AS hour, mb.paymentAmt, mb.paymentDiscount, mb.transportprice, mb.orderstatus')
//            ->join('App\Entity\GlobalAuthen', 'g', 'WITH', 'mb.adminid = g.id')
            ->where('mb.takeorderby = :takeorderby AND mb.adminid = :adminId')
            ->setParameter('takeorderby', $takeorderby)
            ->setParameter('adminId', $adminId)
            ->getQuery()
            ->getResult();
    }

    public function getInvoiceByTakeOrderByAndId($takeorderby, $adminId, $startDate, $lastDate)
    {
        return $this->createQueryBuilder('mb')
            ->select('mb.paymentInvoice, mb.takeorderby, mb.ordername, mb.orderdate, mbdy.sendmaildate, 
            mbdy.mailcode, mb.orderstatus, mb.paymentAmt, mb.transportprice, mb.paymentDiscount')
            ->leftJoin('App\Entity\MerchantBillingDelivery', 'mbdy', 'WITH',
                'mb.takeorderby = mbdy.takeorderby AND mb.paymentInvoice = mbdy.paymentInvoice')
            ->where('mb.takeorderby = :takeorderby AND mb.adminid = :adminId')
            ->andWhere('mb.orderdate BETWEEN :lastDate AND :startDate')
            ->setParameter('takeorderby', $takeorderby)
            ->setParameter('adminId', $adminId)
            ->setParameter('startDate', $startDate)
            ->setParameter('lastDate', $lastDate)
            ->orderBy('mb.orderdate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getInvoiceByOrderStatus($takeorderby, $adminId, $orderStatus, $date)
    {
        return $this->createQueryBuilder('mb')
            ->select('mb.paymentInvoice, mb.takeorderby, mb.ordername, mb.orderdate, mbdy.sendmaildate, 
            mbdy.mailcode, mb.orderstatus, mb.paymentAmt, mb.transportprice, mb.paymentDiscount')
            ->leftJoin('App\Entity\MerchantBillingDelivery', 'mbdy', 'WITH',
                'mb.takeorderby = mbdy.takeorderby AND mb.paymentInvoice = mbdy.paymentInvoice')
            ->where('mb.takeorderby = :takeorderby AND mb.adminid = :adminId AND mb.orderstatus = :orderStatus')
            ->andWhere('DATE_FORMAT(mb.orderdate, \'%Y-%m\') = :date')
            ->setParameter('takeorderby', $takeorderby)
            ->setParameter('adminId', $adminId)
            ->setParameter('orderStatus', $orderStatus)
            ->setParameter('date', $date)
            ->orderBy('mb.orderdate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getInvoiceBySearchFilter($takeorderby, $adminId, $search)
    {
        return $this->createQueryBuilder('mb')
            ->select('mb.paymentInvoice, mb.takeorderby, mb.ordername, mb.orderdate, mbdy.sendmaildate, 
            mbdy.mailcode, mb.orderstatus, mb.paymentAmt, mb.transportprice, mb.paymentDiscount')
            ->leftJoin('App\Entity\MerchantBillingDelivery', 'mbdy', 'WITH',
                'mb.takeorderby = mbdy.takeorderby AND mb.paymentInvoice = mbdy.paymentInvoice')
            ->where('mb.takeorderby = :takeorderby AND mb.adminid = :adminId')
            ->andWhere('mb.paymentInvoice LIKE :paymentInvoice')
            ->setParameter('takeorderby', $takeorderby)
            ->setParameter('adminId', $adminId)
            ->setParameter('paymentInvoice', $search . '%')
            ->orderBy('mb.orderdate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getInvoiceByDateFilter($takeorderby, $adminId, $startDate, $endDate)
    {
        return $this->createQueryBuilder('mb')
            ->select('mb.paymentInvoice, mb.takeorderby, mb.ordername, mb.orderdate, mbdy.sendmaildate, 
            mbdy.mailcode, mb.orderstatus, mb.paymentAmt, mb.transportprice, mb.paymentDiscount')
            ->leftJoin('App\Entity\MerchantBillingDelivery', 'mbdy', 'WITH',
                'mb.takeorderby = mbdy.takeorderby AND mb.paymentInvoice = mbdy.paymentInvoice')
            ->where('mb.takeorderby = :takeorderby AND mb.adminid = :adminId')
            ->andWhere('mb.orderdate BETWEEN :startDate AND :endDate')
            ->setParameter('takeorderby', $takeorderby)
            ->setParameter('adminId', $adminId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('mb.orderdate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getInvoiceByAllFilter($takeorderby, $adminId, $search, $startDate, $endDate)
    {
        return $this->createQueryBuilder('mb')
            ->select('mb.paymentInvoice, mb.takeorderby, mb.ordername, mb.orderdate, mbdy.sendmaildate, 
            mbdy.mailcode, mb.orderstatus, mb.paymentAmt, mb.transportprice, mb.paymentDiscount')
            ->leftJoin('App\Entity\MerchantBillingDelivery', 'mbdy', 'WITH',
                'mb.takeorderby = mbdy.takeorderby AND mb.paymentInvoice = mbdy.paymentInvoice')
            ->where('mb.takeorderby = :takeorderby AND mb.adminid = :adminId AND mb.paymentInvoice LIKE :paymentInvoice')
            ->andWhere('mb.orderdate BETWEEN :endDate AND :startDate')
            ->setParameter('takeorderby', $takeorderby)
            ->setParameter('adminId', $adminId)
            ->setParameter('paymentInvoice', $search . '%')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('mb.orderdate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // report In the Week

    public function getDataInTheWeek($takeorderby, $adminId, $startDate, $lastDate)
    {
        return $this->createQueryBuilder('mb')
            ->select('mb.paymentdate, mb.paymentInvoice, mb.takeorderby, mb.paymentDiscount, mbd.productname, mbd.productorder, mbd.productCommission, mbd.afaCommissionValue, mbd.afaCommissionBonus')
            ->leftJoin('App\Entity\MerchantBillingDetail', 'mbd', 'WITH',
                'mb.takeorderby = mbd.takeorderby AND mb.paymentInvoice = mbd.paymentInvoice')
            ->leftJoin('App\Entity\MerchantBillingDelivery', 'mbdy', 'WITH',
                'mb.takeorderby = mbdy.takeorderby AND mb.paymentInvoice = mbdy.paymentInvoice')
            ->where('mb.takeorderby = :takeorderby AND mb.adminid = :adminId')
            ->andWhere('mb.paymentdate BETWEEN :startDate AND :lastDate AND mb.paymentdate is not null')
            ->setParameter('takeorderby', $takeorderby)
            ->setParameter('adminId', $adminId)
            ->setParameter('startDate', $startDate)
            ->setParameter('lastDate', $lastDate)
            ->orderBy('mb.paymentdate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getDataToChart($takeorderby, $adminId, $thisDate, $lastDate)
    {
        return $this->createQueryBuilder('aa')
            ->select('aa.adminid, bb.productname, bb.productprice, bb.productorder, bb.productCommission, aa.orderstatus, date(aa.orderdate) As orderdate, cc.producttag, cc.productCategories, bb.deliveryFeeInPrice, bb.deliveryFee')
            ->leftJoin('App\Entity\MerchantBillingDetail', 'bb', 'WITH',
                'aa.takeorderby = bb.takeorderby AND aa.paymentInvoice = bb.paymentInvoice')
            ->leftJoin('App\Entity\GlobalProduct', 'cc', 'WITH', 'bb.globalProductid = cc.productid')
            ->where('aa.takeorderby = :takeorderby AND aa.adminid = :adminId')
            ->andwhere('aa.orderstatus != \'101\' AND aa.orderstatus != \'106\'')
            ->andWhere('date(aa.orderdate) > =:lastDate AND date(aa.orderdate) < =:thisDate')
            ->setParameter('takeorderby', $takeorderby)
            ->setParameter('thisDate', $thisDate)
            ->setParameter('lastDate', $lastDate)
            ->setParameter('adminId', $adminId)
            ->orderBy('date(aa.orderdate)', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getDataAFAToChart($takeorderby, $adminId, $thisDate, $lastDate)
    {
        return $this->createQueryBuilder('aa')
            ->select('aa.adminid, bb.productname, bb.productprice, bb.productorder, bb.afaCommissionValue, bb.afaCommissionBonus, aa.orderstatus, date(aa.orderdate) As orderdate, cc.producttag, cc.productCategories, bb.deliveryFeeInPrice, bb.deliveryFee')
            ->leftJoin('App\Entity\MerchantBillingDetail', 'bb', 'WITH',
                'aa.takeorderby = bb.takeorderby AND aa.paymentInvoice = bb.paymentInvoice')
            ->leftJoin('App\Entity\GlobalProduct', 'cc', 'WITH', 'bb.globalProductid = cc.productid')
            ->where('aa.takeorderby = :takeorderby AND aa.adminid = :adminId')
            ->andwhere('aa.orderstatus != \'101\' AND aa.orderstatus != \'106\'')
            ->andWhere('date(aa.orderdate) > =:lastDate AND date(aa.orderdate) < =:thisDate')
            ->setParameter('takeorderby', $takeorderby)
            ->setParameter('thisDate', $thisDate)
            ->setParameter('lastDate', $lastDate)
            ->setParameter('adminId', $adminId)
            ->orderBy('date(aa.orderdate)', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
