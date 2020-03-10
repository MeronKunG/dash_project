<?php

namespace App\Controller;

use App\Repository\AfaComCheckDiscountRepository;
use App\Repository\AfaComCollectComRepository;
use App\Repository\AfaComRefCheckRepository;
use App\Repository\AfaComRefTransferRepository;
use App\Repository\HdComCheckDiscountRepository;
use App\Repository\HdComCheckPriceComRepository;
use App\Repository\HdComCollectComRepository;
use App\Repository\HdComRefCheckRepository;
use App\Repository\HdComRefTransferRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class CommissionController extends AbstractController
{
    /**
     * @Route("/commission", name="commission")
     */
    public function commission(
        Request $request,
        PaginatorInterface $paginator,
        HdComRefTransferRepository $hdComRefTransferRepository,
        HdComRefCheckRepository $hdComRefCheckRepository,
        HdComCheckDiscountRepository $hdComCheckDiscountRepository,
        HdComCollectComRepository $hdComCollectComRepository,
        AfaComCollectComRepository $afaComCollectComRepository,
        AfaComCheckDiscountRepository $afaComCheckDiscountRepository,
        AfaComRefCheckRepository $afaComRefCheckRepository,
        AfaComRefTransferRepository $afaComRefTransferRepository
    )
    {
        $session = new Session();
        $results2 = [];
        $total = [];
        $commissionObj = [];

        if ($request->query->get('comref') != null) {
            $total1 = [];
            $total2 = [];
            $total3 = [];
            if ($request->query->get('comref') != null && $request->query->get('check') === 'com_check') {
                if ($session->get('merType') == "afa") {
                    $commissionObj1 = $afaComCollectComRepository->getAllDataComCheck($this->getUser()->getId(),
                        $request->query->get('comref'));
                    foreach ($commissionObj1 as $val) {
                        $total1[] = $val['totalCom'];
                        $total2[] = $val['adjustedPrice'];
                        $total3[] = $val['totalBonus'];
                    }
                    $total = array('total1' => array_sum($total1), 'total2' => array_sum($total2), 'total3' => array_sum($total3));
                } else {
                    $commissionObj1 = $hdComCollectComRepository->getAllDataComCheck($this->getUser()->getId(),
                        $request->query->get('comref'));
                    foreach ($commissionObj1 as $val) {
                        $total1[] = $val['totalCom'];
                        $total2[] = $val['adjustedPrice'];
                    }
                    $total = array('total1' => array_sum($total1), 'total2' => array_sum($total2));
                }
            } elseif ($request->query->get('comref') != null && $request->query->get('check') === 'discount_check') {
                if ($session->get('merType') == "afa") {
                    $commissionObj1 = $afaComCheckDiscountRepository->getAllDataDiscountCheck($this->getUser()->getId(),
                        $request->query->get('comref'));
                } else {
                    $commissionObj1 = $hdComCheckDiscountRepository->getAllDataDiscountCheck($this->getUser()->getId(),
                        $request->query->get('comref'));
                }
                foreach ($commissionObj1 as $val) {
                    $total1[] = $val['discount'];
                }
                $total = array('total1' => array_sum($total1));
            } else {
                if ($session->get('merType') == "afa") {
                    $commissionObj1 = $afaComRefCheckRepository->findBy(array('initialRef' => $request->query->get('comref')));
                } else {
                    $commissionObj1 = $hdComRefCheckRepository->findBy(array('initialRef' => $request->query->get('comref')));
                }
            }
            $results2 = $paginator->paginate(
                $commissionObj1, /* query NOT result */
                $request->query->getInt('page', 1)/*page number*/,
                10/*limit per page*/
            );
        } else {
            if ($session->get('merType') == "afa") {
                $commissionObj = $afaComRefTransferRepository->getCommissionDataByAdminId($this->getUser()->getId());
            } else {
                $commissionObj = $hdComRefTransferRepository->getCommissionDataByAdminId($this->getUser()->getId());
            }

        }
        $results = $paginator->paginate(
            $commissionObj, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            10/*limit per page*/
        );
        return $this->render('commission/index.html.twig', [
            'commissionData' => $results,
            'commissionData2' => $results2,
            'total' => $total,
        ]);
    }
}
