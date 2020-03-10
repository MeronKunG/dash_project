<?php

namespace App\Controller;

use App\Entity\GlobalOrderstatus;
use App\Entity\TestSalesComDaily;
use App\Entity\TestSalesComMonthly;
use App\Entity\TestSalesComTotal;
use App\Entity\TestSalesVolumeDaily;
use App\Entity\TestSalesVolumeMonthly;
use App\Entity\TestSalesVolumeTotal;
use App\Form\FilterType;
use App\Repository\AccWithholdingTaxRepository;
use App\Repository\GlobalAuthenRepository;
use App\Repository\GlobalOrderstatusRepository;
use App\Repository\MerchantBillingDetailRepository;
use App\Repository\MerchantBillingRepository;
use App\Repository\TestSalesComDailyRepository;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\AreaChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use Knp\Component\Pager\PaginatorInterface;
use Ob\HighchartsBundle\Highcharts\Highchart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends AbstractController
{
    protected $accWithholdingTaxRepository;
    protected $globalOrderstatusRepository;

    /**
     * @var GlobalOrderstatusRepository
     */

    public function __construct(
        AccWithholdingTaxRepository $accWithholdingTaxRepository,
        GlobalOrderstatusRepository $globalOrderstatusRepository
    )
    {
        date_default_timezone_set("Asia/Bangkok");

        $this->accWithholdingTaxRepository = $accWithholdingTaxRepository;
        $this->globalOrderstatusRepository = $globalOrderstatusRepository;
    }

    /**
     * @Route("/list", name="list")
     */
    public function list(
        Request $request,
        PaginatorInterface $paginator,
        AccWithholdingTaxRepository $accWithholdingTaxRepository
    )
    {
        date_default_timezone_set("Asia/Bangkok");
        $date = date("Y-m");
        $taxDataObj = $accWithholdingTaxRepository->getFileNameByUserId($this->getUser()->getId(), $date);
        foreach ($taxDataObj as $key => $val) {
            foreach ($val as $k => $v) {
                if ($k === 'uploadedBy') {
                    $taxDataObj[$key]['uploadedBy'] = $this->changeIdToName($v);
                }
            }
        }
        $results = $paginator->paginate(
            $taxDataObj, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            10/*limit per page*/
        );
        $startDate = $request->query->get('sdate');
        $endDate = $request->query->get('edate');
        if ($startDate || $endDate) {
            $con_startDate = date("Y-m-d", strtotime($startDate));
            $con_endDate = date("Y-m-d", strtotime($endDate));
            if ($startDate != null && $endDate != null) {
                $taxDataObj = $accWithholdingTaxRepository->findDateQuery($this->getUser()->getId(), $con_startDate,
                    $con_endDate);
            } elseif ($startDate != null && $endDate == null) {
                $this->addFlash('error', 'กรุณาระบุวันที่ให้ครบถ้วน');
                return $this->redirect($this->generateUrl('list'));
            }
            foreach ($taxDataObj as $key => $val) {
                foreach ($val as $k => $v) {
                    if ($k === 'uploadedBy') {
                        $taxDataObj[$key]['uploadedBy'] = $this->changeIdToName($v);
                    }
                }
            }
            $results = $paginator->paginate(
                $taxDataObj,
                $request->query->getInt('page', 1)/*page number*/,
                10/*limit per page*/
            );
        }
        return $this->render('profile/profile.html.twig',
            [
                'taxData' => $results,
            ]);
    }

    /**
     * @Route("/report", name="report")
     */
    public function report(
        Request $request,
        PaginatorInterface $paginator,
        MerchantBillingRepository $merchantBillingRepository,
        MerchantBillingDetailRepository $merchantBillingDetailRepository
    )
    {
        date_default_timezone_set("Asia/Bangkok");
        $date = date("Y-m");
        $form = $this->createForm(FilterType::class);
        $form->handleRequest($request);
        $merchantData = [];
        $search = $request->query->get('search');
        $stDate = $request->query->get('sdate');
        $edDate = $request->query->get('edate');
        $selectDate = $request->query->get('date');
        $status = $request->query->get('status');
        $reportDataObj = $merchantBillingRepository->countInvoice($this->getUser()->getMerId(),
            $this->getUser()->getId());
        foreach ($reportDataObj as $key => $val) {
            $merchantData[$this->changeIdToStatusName($val['orderstatus'])][] = $val['orderstatus'];
        }
        if (isset($search)) {
            if ($search != null) {
                $reportDataObj = $merchantBillingRepository->getInvoiceBySearchFilter($this->getUser()->getMerId(),
                    $this->getUser()->getId(), $search);
            } else {
                $this->addFlash('error', 'กรุณาระบุ Invoice');
                return $this->redirect($this->generateUrl('report'));
            }

            foreach ($reportDataObj as $key => $val) {
                $reportDataObj[$key]['result'] = '' . ($reportDataObj[$key]['paymentAmt'] + $reportDataObj[$key]['transportprice']) - $reportDataObj[$key]['paymentDiscount'];
                $reportDataObj[$key]['merchantObj'] = $merchantBillingDetailRepository->getProductAndCommissionByTakeOrderBy($this->getUser()->getMerId(),
                    $reportDataObj[$key]['paymentInvoice']);
                foreach ($reportDataObj[$key]['merchantObj'] as $k => $v) {
                    $reportDataObj[$key]['merchantObj'][$k]['result'] = ($reportDataObj[$key]['merchantObj'][$k]['productCommission'] * $reportDataObj[$key]['merchantObj'][$k]['productorder']);
                }
                foreach ($val as $k => $v) {
                    if ($k === 'orderstatus') {
                        $reportDataObj[$key]['orderstatus'] = $this->changeIdToStatusName($v);
                    }
                }
            }
            $results = $paginator->paginate(
                $reportDataObj,
                $request->query->getInt('page', 1)/*page number*/,
                10/*limit per page*/
            );
        } elseif (isset($stDate) || isset($edDate)) {
            $con_startDate = date("Y-m-d", strtotime($stDate));
            $con_endDate = date("Y-m-d", strtotime($edDate));
            if ($stDate != null && $edDate != null) {
                $reportDataObj = $merchantBillingRepository->getInvoiceByDateFilter($this->getUser()->getMerId(),
                    $this->getUser()->getId(), $con_startDate, $con_endDate);
            } elseif ($stDate != null && $edDate == null) {
                $this->addFlash('error', 'กรุณาระบุวันที่ให้ครบถ้วน');
                return $this->redirect($this->generateUrl('report'));
            }

            foreach ($reportDataObj as $key => $val) {
                $reportDataObj[$key]['result'] = '' . ($reportDataObj[$key]['paymentAmt'] + $reportDataObj[$key]['transportprice']) - $reportDataObj[$key]['paymentDiscount'];
                $reportDataObj[$key]['merchantObj'] = $merchantBillingDetailRepository->getProductAndCommissionByTakeOrderBy($this->getUser()->getMerId(),
                    $reportDataObj[$key]['paymentInvoice']);
                foreach ($reportDataObj[$key]['merchantObj'] as $k => $v) {
                    $reportDataObj[$key]['merchantObj'][$k]['result'] = ($reportDataObj[$key]['merchantObj'][$k]['productCommission'] * $reportDataObj[$key]['merchantObj'][$k]['productorder']);
                }
                foreach ($val as $k => $v) {
                    if ($k === 'orderstatus') {
                        $reportDataObj[$key]['orderstatus'] = $this->changeIdToStatusName($v);
                    }
                }
            }
            $results = $paginator->paginate(
                $reportDataObj,
                $request->query->getInt('page', 1)/*page number*/,
                10/*limit per page*/
            );
        } elseif (isset($selectDate) && isset($status)) {
            $reportDataObj = $merchantBillingRepository->getInvoiceByOrderStatus($this->getUser()->getMerId(),
                $this->getUser()->getId(), $request->query->get('status'), $selectDate);
            foreach ($reportDataObj as $key => $val) {
                $reportDataObj[$key]['result'] = '' . ($reportDataObj[$key]['paymentAmt'] + $reportDataObj[$key]['transportprice']) - $reportDataObj[$key]['paymentDiscount'];
                $reportDataObj[$key]['merchantObj'] = $merchantBillingDetailRepository->getProductAndCommissionByTakeOrderBy($this->getUser()->getMerId(),
                    $reportDataObj[$key]['paymentInvoice']);
                foreach ($reportDataObj[$key]['merchantObj'] as $k => $v) {
                    $reportDataObj[$key]['merchantObj'][$k]['result'] = ($reportDataObj[$key]['merchantObj'][$k]['productCommission'] * $reportDataObj[$key]['merchantObj'][$k]['productorder']);
                }
                foreach ($val as $k => $v) {
                    if ($k === 'orderstatus') {
                        $reportDataObj[$key]['orderstatus'] = $this->changeIdToStatusName($v);
                    }
                }
            }
            $results = $paginator->paginate(
                $reportDataObj, /* query NOT result */
                $request->query->getInt('page', 1)/*page number*/,
                10/*limit per page*/
            );
        } else {
            $startDate = date("Y-m-d");
            $lastDate = date('Y-m-d', strtotime($startDate . "-7 days"));
            $reportDataObj = $merchantBillingRepository->getInvoiceByTakeOrderByAndId($this->getUser()->getMerId(),
                $this->getUser()->getId(), $startDate, $lastDate);
            foreach ($reportDataObj as $key => $val) {
                $reportDataObj[$key]['result'] = '' . ($reportDataObj[$key]['paymentAmt'] + $reportDataObj[$key]['transportprice']) - $reportDataObj[$key]['paymentDiscount'];
                $reportDataObj[$key]['merchantObj'] = $merchantBillingDetailRepository->getProductAndCommissionByTakeOrderBy($this->getUser()->getMerId(),
                    $reportDataObj[$key]['paymentInvoice']);
                foreach ($reportDataObj[$key]['merchantObj'] as $k => $v) {
                    $reportDataObj[$key]['merchantObj'][$k]['result'] = ($reportDataObj[$key]['merchantObj'][$k]['productCommission'] * $reportDataObj[$key]['merchantObj'][$k]['productorder']);
                }
                foreach ($val as $k => $v) {
                    if ($k === 'orderstatus') {
                        $reportDataObj[$key]['orderstatus'] = $this->changeIdToStatusName($v);
                    }
                }
            }

            $results = $paginator->paginate(
                $reportDataObj, /* query NOT result */
                $request->query->getInt('page', 1)/*page number*/,
                10/*limit per page*/
            );
        }
        return $this->render('profile/report.html.twig',
            [
                'reportData' => $results,
                'testdata' => $merchantData,
                'date' => $date,
                'form' => $form->createView(),
            ]);
    }

    /**
     * @Route("/report_week", name="report_week")
     */
    public
    function report_week(
        Request $request,
        PaginatorInterface $paginator,
        MerchantBillingRepository $merchantBillingRepository,
        MerchantBillingDetailRepository $merchantBillingDetailRepository
    )
    {
        date_default_timezone_set("Asia/Bangkok");
        $session = new Session();
        $previous_monday = date('Y-m-d', strtotime('monday this week'));
        $next_sunday = date('Y-m-d', strtotime('sunday this week'));
        $com_results = [];
        $discount_results = [];
        $reportDataObj = $merchantBillingRepository->getDataInTheWeek($this->getUser()->getMerId(),
            $this->getUser()->getId(), $previous_monday, $next_sunday);
        $com_result = 0;
        foreach ($reportDataObj as $key => $val) {
            if ($session->get('merType') == "afa") {
                $reportDataObj[$key]['result'] = ($reportDataObj[$key]['afaCommissionValue'] * $reportDataObj[$key]['productorder']);
            } else {
                $reportDataObj[$key]['result'] = ($reportDataObj[$key]['productCommission'] * $reportDataObj[$key]['productorder']);
            }
        }
        foreach ($reportDataObj as $key => $val) {
            $reportDataObj[$key]['com_result'] = $com_result + $reportDataObj[$key]['result'];
            $com_results[] = $reportDataObj[$key]['com_result'];
            $discount_results[] = $reportDataObj[$key]['paymentDiscount'];
        }
        $total = [
            'com' => array_sum($com_results),
            'discount' => array_sum($discount_results),
            'transfer' => (array_sum($com_results) - array_sum($discount_results))
        ];
        $results = $paginator->paginate(
            $reportDataObj, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            10/*limit per page*/
        );
        return $this->render('profile/report_week.html.twig',
            [
                'reportData' => $results,
                'total' => $total
            ]);
    }

    function changeIdToStatusName($id)
    {
        $data = $id;
        if ($id == '101') {
            $data = "ยกเลิกรายการ";
        } elseif ($id == '102') {
            $data = "รอชำระเงิน";
        } elseif ($id == '103') {
            $data = "ชำระเงินแล้ว";
        } elseif ($id == '104') {
            $data = "จัดส่งแล้ว";
        } elseif ($id == '105') {
            $data = "สินค้าถึงปลายทาง";
        } elseif ($id == '106') {
            $data = "สินค้าตีกลับ";
        }
        return $data;
    }

    function changeIdToName($userId)
    {
        $userId = trim($userId);
        $userId = $this->accWithholdingTaxRepository->getUploadNameByUserId($userId);
        $userId = $userId[0]['displayname'];
        return $userId;
    }
}
