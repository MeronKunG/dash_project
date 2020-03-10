<?php

namespace App\Controller;

use App\Entity\TestSalesComDaily;
use App\Entity\TestSalesComMonthly;
use App\Entity\TestSalesComTotal;
use App\Entity\TestSalesVolumeDaily;
use App\Entity\TestSalesVolumeMonthly;
use App\Entity\TestSalesVolumeTotal;
use App\Repository\GlobalAuthenRepository;
use App\Repository\MerchantBillingDetailRepository;
use App\Repository\MerchantBillingRepository;
use Knp\Component\Pager\PaginatorInterface;
use Ob\HighchartsBundle\Highcharts\Highchart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;

class DashboardController extends AbstractController
{
    /**
     * @Route("/dashboard", name="dashboard")
     */
    public
    function dashboard(
        Request $request,
        PaginatorInterface $paginator,
        MerchantBillingRepository $merchantBillingRepository,
        MerchantBillingDetailRepository $merchantBillingDetailRepository
    )
    {
        date_default_timezone_set("Asia/Bangkok");
        $session = new Session();

        $orderDateData = [];
        $orderDateDataHour = [];

        $client = HttpClient::create();
        $data = json_encode([
            'takeorderby' => $this->getUser()->getMerId(),
            'adminid' => $this->getUser()->getId()
        ]);

        $response = $client->request('POST', 'https://api-mission.945.report/report/getDataFullScore', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => $data,
        ]);
        $content = $response->toArray();
        if(empty($content)) {
            $content = [
                0 => [
                    "raw_score" => 0,
                    "pct" => 0,
                    "mission_full_score" => 300,
                    "mission_name" => "YADA 300"
                ],
                1 => [
                    "raw_score" => 0,
                    "pct" => 0,
                    "mission_full_score" => 300,
                    "mission_name" => "CATANDU 300"
                ],
                2 => [
                    "raw_score" => 0,
                    "pct" => 0,
                    "mission_full_score" => 300,
                    "mission_name" => "SIMPLY 300"
                ]
            ];
        }

        $orderDateRawData = $merchantBillingRepository->getDateByInvoice($this->getUser()->getMerId(),
            $this->getUser()->getId());

        foreach ($orderDateRawData as $key => $val) {
            $orderDateData[$key] = $val['orderdate'];
            $orderDateDataHour[$val['hour']][] = $val;
        }
        $filtered = array_filter(
            $orderDateRawData,
            function ($k) use ($orderDateRawData) {
                return $orderDateRawData[$k]['orderdate']->format('Y-m-d') == date("Y-m-d");
            },
            ARRAY_FILTER_USE_KEY
        );
        $filtered = array_values($filtered);
        $hours = [];
        foreach ($filtered as $key => $val) {
            $filtered[$key]['total'] = ($val['paymentAmt'] + $val['transportprice']) - $val['paymentDiscount'];
        }
        $test6 = [];
        foreach ($filtered as $key => $val) {
            $test6[$val['hour']][] = $val;
        }

        foreach ($test6 as $key => $val) {
            $test6[$key]['total'] = 0;
            foreach ($val as $k => $v) {
                $test6[$key]['total'] = $test6[$key]['total'] + $v['total'];
            }
            $hours[] = $key;
            $test6[$key] = ['' . $key, $test6[$key]['total']];
        }
        $test6 = array_values($test6);
        $chart3[] = ['type' => 'bar', 'color' => '#E75480', 'name' => 'จำนวน', 'data' => $test6];

        $minDate = min($orderDateData);

        $key = array_search($minDate, $orderDateData);

        $dateLowData = $orderDateRawData[$key];
        $dateRaw1 = $dateLowData['orderdate']->format('Y-m-d');
        $dateRaw2 = date("Y-m-d");

        $date1 = strtotime($dateRaw1);
        $date2 = strtotime($dateRaw2);

        $diff = abs($date2 - $date1);

        $years = floor($diff / (365 * 60 * 60 * 24));

        $months = floor(($diff - $years * 365 * 60 * 60 * 24)
            / (30 * 60 * 60 * 24));

        $days = floor(($diff - $years * 365 * 60 * 60 * 24 -
                $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

        $dateTime_live = [
            'years' => $years,
            'months' => $months,
            'days' => $days
        ];
        $previous_monday = date('Y-m-d', strtotime('monday this week'));
        $next_sunday = date('Y-m-d', strtotime('sunday this week'));
        if (empty($session->get('startWeek')) && empty($session->get('endWeek'))) {
            $session->set('startWeek', $previous_monday);
            $session->set('endWeek', $next_sunday);
        }

        if ($request->query->get('date') == 'lastWeek') {
            $session->set('startWeek', date('Y-m-d', strtotime('monday this week -7 days')));
            $session->set('endWeek', date('Y-m-d', strtotime('sunday this week -7 days')));
            return $this->redirect($this->generateUrl('dashboard'));
        } elseif ($request->query->get('date') == '2Week') {
            $session->set('startWeek', date('Y-m-d', strtotime('monday this week -14 days')));
            $session->set('endWeek', date('Y-m-d', strtotime('sunday this week -14 days')));
            return $this->redirect($this->generateUrl('dashboard'));
        } elseif ($request->query->get('date') == '3Week') {
            $session->set('startWeek', date('Y-m-d', strtotime('monday this week -21 days')));
            $session->set('endWeek', date('Y-m-d', strtotime('sunday this week -21 days')));
            return $this->redirect($this->generateUrl('dashboard'));
        } elseif ($request->query->get('date') == 'thisWeek') {
            $session->set('startWeek', $previous_monday);
            $session->set('endWeek', $next_sunday);
            return $this->redirect($this->generateUrl('dashboard'));
        }
        $data = [];
        $merchantData = $merchantBillingDetailRepository->getProductAndOrder($this->getUser()->getId(),
            $this->getUser()->getMerId(), $session->get('startWeek'), $session->get('endWeek'));
        if (!empty($merchantData)) {
            foreach ($merchantData as $item) {
                $data[$item['productname']][] = [
                    'path' => $this->cutSyntaxDoublePoint($item['path']),
                    'dayName' => $this->changeDateName($item['dateOnly']),
                    'amounts' => $item['amounts'],
                    'name' => $item['productname']
                ];
            }

            foreach ($data as $key => $val) {
                $week = [
                    'Monday' => 0,
                    'Tuesday' => 0,
                    'Wednesday' => 0,
                    'Thursday' => 0,
                    'Friday' => 0,
                    'Saturday' => 0,
                    'Sunday' => 0
                ];
                $data[$key]['total'] = 0;
                foreach ($val as $itemVal) {
                    if (array_key_exists($itemVal['dayName'], $week)) {
                        $week[$itemVal['dayName']] = intval($itemVal['amounts']);
                    } else {
                        $week[$itemVal['dayName']] = 0;
                    }
                    $data[$key]['total'] = ($data[$key]['total'] + intval($itemVal['amounts']));
                }
                $dataResult[$key] = [
                    'path' => $val[0]['path'],
                    'name' => $val[0]['name'],
                    'week' => $week,
                    'total' => $data[$key]['total']
                ];
            }
            usort($dataResult, function ($item1, $item2) {
                return $item2['total'] <=> $item1['total'];
            });


            $results = $paginator->paginate(
                $dataResult, /* query NOT result */
                $request->query->getInt('page', 1)/*page number*/,
                10
            );
        } else {
            $results = [];
        }
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////

        $total1 = $this->getDoctrine()->getRepository(TestSalesComDaily::class)->findOneBy(array('adminid' => $this->getUser()->getId()));
        if ($total1 == null) {
            $total1 = 0;
        } else {
            $total1 = $total1->getComAmt();
        }
        $total2 = $this->getDoctrine()->getRepository(TestSalesComMonthly::class)->findOneBy(array('adminid' => $this->getUser()->getId()));
        if ($total2 == null) {
            $total2 = 0;
        } else {
            $total2 = $total2->getComAmt();
        }
        $total3 = $this->getDoctrine()->getRepository(TestSalesComTotal::class)->findOneBy(array('adminid' => $this->getUser()->getId()));
        if ($total3 == null) {
            $total3 = 0;
        } else {
            $total3 = $total3->getComAmt();
        }
        $total4 = $this->getDoctrine()->getRepository(TestSalesVolumeDaily::class)->findOneBy(array('adminid' => $this->getUser()->getId()));
        if ($total4 == null) {
            $total4 = 0;
        } else {
            $total4 = $total4->getComAmt();
        }
        $total5 = $this->getDoctrine()->getRepository(TestSalesVolumeMonthly::class)->findOneBy(array('adminid' => $this->getUser()->getId()));
        if ($total5 == null) {
            $total5 = 0;
        } else {
            $total5 = $total5->getComAmt();
        }
        $total6 = $this->getDoctrine()->getRepository(TestSalesVolumeTotal::class)->findOneBy(array('adminid' => $this->getUser()->getId()));
        if ($total6 == null) {
            $total6 = 0;
        } else {
            $total6 = $total6->getComAmt();
        }

        $total_result = [
            't1' => $total1,
            't2' => $total2,
            't3' => $total3,
            't4' => $total4,
            't5' => $total5,
            't6' => $total6
        ];

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////

        $thisDate = date("Y-m-d");
        $lastDate = date("Y-m-d", strtotime("-2 month"));
        if (empty($session->get('thisDate')) && empty($session->get('lastDate'))) {
            $session->set('thisDate', $thisDate);
            $session->set('lastDate', $lastDate);
        }

        if ($request->query->get('date') == 'twoMonth') {
            $session->set('thisDate', date("Y-m-d"));
            $session->set('lastDate', date("Y-m-d", strtotime("-2 month")));
            return $this->redirect($this->generateUrl('dashboard'));
        } elseif ($request->query->get('date') == 'oneMonth') {
            $session->set('thisDate', date("Y-m-d"));
            $session->set('lastDate', date("Y-m-d", strtotime("-1 month")));
            return $this->redirect($this->generateUrl('dashboard'));
        } elseif ($request->query->get('date') == 'fourteen') {
            $session->set('thisDate', date("Y-m-d"));
            $session->set('lastDate', date("Y-m-d", strtotime("-2 week")));
            return $this->redirect($this->generateUrl('dashboard'));
        } elseif ($request->query->get('date') == 'seven') {
            $session->set('thisDate', date("Y-m-d"));
            $session->set('lastDate', date("Y-m-d", strtotime("-1 week")));
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $merchantRawDataChart = $merchantBillingRepository->getDataToChart($this->getUser()->getMerId(),
            $this->getUser()->getId(), $session->get('thisDate'), $session->get('lastDate'));;
        $listCat = [];
        $list = [];
        $list2 = [];
        $listItem = [];
        $listItem2 = [];
        $xAxis = [];
        foreach ($merchantRawDataChart as $item) {
            $listItem[$item['orderdate']][] = [
                'total' => ($item['productprice'] * $item['productorder']) + $item['deliveryFee'],
                'cat' => $item['productCategories']
            ];
            $listItem2[$item['orderdate']][] = [
                'total' => $item['productCommission'] * $item['productorder'],
                'cat' => $item['productCategories']
            ];
            $listCat[$item['productCategories']] = 0;//สร้างชุดข้อมูล Categories ก่อน
        }

        foreach ($listCat as $key => $val) {
            if (array_key_exists('ไร่สุพรรณ', $listCat)) {
                $listCat = array('ไร่สุพรรณ' => 0) + $listCat;
            }
            if (array_key_exists('สัตว์เลี้ยง', $listCat)) {
                $listCat = array('สัตว์เลี้ยง' => 0) + $listCat;
            }
            if (array_key_exists('อาหารเสริม', $listCat)) {
                $listCat = array('อาหารเสริม' => 0) + $listCat;
            }
        }

        $dataTest = [];
        foreach ($listItem as $key => $value) {
            foreach ($listCat as $k => $v) {
                $list[$k] = 0;
            }
            foreach ($value as $dataItem) {
                if (array_key_exists($dataItem['cat'], $list)) {
                    $list[$dataItem['cat']] += $dataItem['total'];
                } else {
                    $list[$dataItem['cat']] = 0;
                }
            }
            $dataTest[$key] = ['listCat' => $list];
        }
        $dataTest2 = [];

        foreach ($listItem2 as $key => $value) {
            foreach ($listCat as $k => $v) {
                $list2[$k] = 0;
            }
            foreach ($value as $dataItem) {
                if (array_key_exists($dataItem['cat'], $list2)) {
                    $list2[$dataItem['cat']] += $dataItem['total'];
                } else {
                    $list2[$dataItem['cat']] = 0;
                }
            }
            $dataTest2[$key] = ['listCat' => $list2];
        }

        $gValue = [];
        $tValue = [];
        $cValue = [];
        $ctValue = [];
        foreach ($dataTest as $date => $lc) {
            $xAxis[] = $date;
            foreach ($lc as $cName => $data) {
                foreach ($data as $n => $vv) {
                    $gValue[$n][] = $vv;
                }
            }
            $tValue[] = 0;
        }

        foreach ($dataTest2 as $date => $lc) {
            foreach ($lc as $cName => $data) {
                foreach ($data as $n => $vv) {
                    $cValue[$n][] = $vv;
                }
            }
            $ctValue[] = 0;
        }

        foreach ($gValue as $key => $val) {
            foreach ($val as $k => $v) {
                $tValue[$k] = $tValue[$k] + $gValue[$key][$k];
            }
        }
        foreach ($cValue as $key => $val) {
            foreach ($val as $k => $v) {
                $ctValue[$k] = $ctValue[$k] + $cValue[$key][$k];
            }
        }

        $chart1[] = ['type' => 'areaspline', 'color' => '#cafca4', 'opacity' => '0.5s', 'ทั้งหมด', 'name' => 'ทั้งหมด', 'data' => $tValue];
        foreach ($gValue as $key => $val) {
            $chart1[] = ['type' => 'areaspline', 'name' => $key, 'data' => $gValue[$key]];
        }

        $chart2[] = [
            'type' => 'areaspline',
            'color' => '#cafca4',
            'opacity' => '0.5s',
            'name' => 'ทั้งหมด',
            'data' => $ctValue

        ];
        foreach ($cValue as $key => $val) {
            $chart2[] = ['type' => 'areaspline', 'name' => $key, 'data' => $cValue[$key]];
        }

        foreach ($chart1 as $key => $val) {
            if (array_search('อาหารเสริม', $chart1[$key])) {
                $chart1[$key]['color'] = '#98b6ed';
                $chart1[$key]['opacity'] = '0.5';

            }
            if (array_search('สัตว์เลี้ยง', $chart1[$key])) {
                $chart1[$key]['color'] = '#c8a3ff';
                $chart1[$key]['opacity'] = '0.5';
            }
            if (array_search('ไร่สุพรรณ', $chart1[$key])) {
                $chart1[$key]['color'] = '#faca84';
                $chart1[$key]['opacity'] = '0.5';

            }
        }

        foreach ($chart2 as $key => $val) {
            if (array_search('อาหารเสริม', $chart2[$key])) {
                $chart2[$key]['color'] = '#98b6ed';
                $chart2[$key]['opacity'] = '0.5';
            }
            if (array_search('สัตว์เลี้ยง', $chart2[$key])) {
                $chart2[$key]['color'] = '#c8a3ff';
                $chart2[$key]['opacity'] = '0.5';
            }
            if (array_search('ไร่สุพรรณ', $chart2[$key])) {
                $chart2[$key]['color'] = '#faca84';
                $chart2[$key]['opacity'] = '0.5';
            }
        }

        $ob = new Highchart();
        $ob->chart->renderTo('linechart');
        $ob->title->text('ยอดขายสินค้า');
        $ob->tooltip->shared(true);
        $ob->tooltip->valueSuffix(' บาท');
        $ob->legend->verticalAlign('top');
        $ob->plotOptions->areaspline(array(
            'fillOpacity' => 0.5
        ));
        $categories = $xAxis;
        $ob->xAxis->categories($categories);
        $ob->yAxis->title(array('text' => 'จำนวนเงิน'));
        $ob->series($chart1);

        ///////////////////////////////////////////////////
        $ob2 = new Highchart();
        $ob2->chart->renderTo('linechart2');
        $ob2->title->text('ยอดคอมมิชชั่น');
        $ob2->tooltip->shared(true);
        $ob2->tooltip->valueSuffix(' บาท');
        $ob2->legend->verticalAlign('top');
        $ob2->plotOptions->areaspline(array(
            'fillOpacity' => 0.5
        ));
        $categories = $xAxis;
        $ob2->xAxis->categories($categories);
        $ob2->yAxis->min(0);
        $ob2->yAxis->title(array('text' => 'จำนวนเงิน'));
        $ob2->series($chart2);

        /////////////////////////////////////////////////////
        ///////////////////////////////////////////////////
        $ob3 = new Highchart();
        $ob3->chart->renderTo('linechart3');
        $ob3->title->text('ยอดขายรายชั่วโมง');
        $ob3->tooltip->valueSuffix(' บาท');
        $ob3->plotOptions->bar(array(
            'dataLabels' => array(
                'enabled' => true
            )
        ));
        $ob3->legend->verticalAlign('top');
        $ob3->xAxis->categories($hours);
        $ob3->xAxis->title(array(
            'text' => null
        ));
        $ob3->yAxis->title(array('text' => 'จำนวนเงิน'));
        $ob3->series($chart3);

        return $this->render('dashboard/dashboard.html.twig',
            [
                'dateTime' => $dateTime_live,
                'data' => $results,
                'data_result' => $total_result,
                'chart' => $ob,
                'chart2' => $ob2,
                'chart3' => $ob3,
                'result' => $content
            ]);
    }

    /**
     * @Route("/dashboard_get", name="dashboard_get")
     */
    public function dashboard_get(
        Request $request,
        PaginatorInterface $paginator,
        MerchantBillingRepository $merchantBillingRepository,
        MerchantBillingDetailRepository $merchantBillingDetailRepository,
        GlobalAuthenRepository $authenRepository
    )
    {
        date_default_timezone_set("Asia/Bangkok");
        $session = new Session();

        $orderDateData = [];
        $orderDateDataHour = [];
        if ($request->query->get('adminid') != null && $request->query->get('takeorderby') != null) {
            $orderDateRawData = $merchantBillingRepository->getDateByInvoice($request->query->get('takeorderby'),
                $request->query->get('adminid'));
//            $userData = $authenRepository->findOneBy(array("id" => $request->query->get('adminid')));
            $userData = $authenRepository->getUserData($request->query->get('adminid'));
            foreach ($userData as $key => $val) {
                foreach ($val as $k => $v) {
                    if ($k === 'phoneno') {
                        $userData[$key]['phoneno'] = $this->doubleSix2Zero($v);
                    }
                }
            }
            if (!empty($orderDateRawData)) {

                foreach ($orderDateRawData as $key => $val) {
                    $orderDateData[$key] = $val['orderdate'];
                    $orderDateDataHour[$val['hour']][] = $val;
                }
                $filtered = array_filter(
                    $orderDateRawData,
                    function ($k) use ($orderDateRawData) {
                        return $orderDateRawData[$k]['orderdate']->format('Y-m-d') == date("Y-m-d");
                    },
                    ARRAY_FILTER_USE_KEY
                );
                $filtered = array_values($filtered);
                $hours = [];
                foreach ($filtered as $key => $val) {
                    $filtered[$key]['total'] = ($val['paymentAmt'] + $val['transportprice']) - $val['paymentDiscount'];
                }
                $test6 = [];
                foreach ($filtered as $key => $val) {
                    $test6[$val['hour']][] = $val;
                }

                foreach ($test6 as $key => $val) {
                    $test6[$key]['total'] = 0;
                    foreach ($val as $k => $v) {
                        $test6[$key]['total'] = $test6[$key]['total'] + $v['total'];
                    }
                    $hours[] = $key;
                    $test6[$key] = ['' . $key, $test6[$key]['total']];
                }
                $test6 = array_values($test6);
                $chart3[] = ['type' => 'bar', 'color' => '#E75480', 'name' => 'จำนวน', 'data' => $test6];

                $minDate = min($orderDateData);

                $key = array_search($minDate, $orderDateData);

                $dateLowData = $orderDateRawData[$key];
                $dateRaw1 = $dateLowData['orderdate']->format('Y-m-d');
                $dateRaw2 = date("Y-m-d");

                $date1 = strtotime($dateRaw1);
                $date2 = strtotime($dateRaw2);

                $diff = abs($date2 - $date1);

                $years = floor($diff / (365 * 60 * 60 * 24));

                $months = floor(($diff - $years * 365 * 60 * 60 * 24)
                    / (30 * 60 * 60 * 24));

                $days = floor(($diff - $years * 365 * 60 * 60 * 24 -
                        $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                $dateTime_live = [
                    'years' => $years,
                    'months' => $months,
                    'days' => $days
                ];
                $previous_monday = date('Y-m-d', strtotime('monday this week'));
                $next_sunday = date('Y-m-d', strtotime('sunday this week'));
                if (empty($session->get('startWeek_get')) && empty($session->get('endWeek_get'))) {
                    $session->set('startWeek_get', $previous_monday);
                    $session->set('endWeek_get', $next_sunday);
                }
                if ($request->query->get('date') == 'lastWeek') {
                    $session->set('startWeek_get', date('Y-m-d', strtotime('monday this week -7 days')));
                    $session->set('endWeek_get', date('Y-m-d', strtotime('sunday this week -7 days')));
                    return $this->redirect($this->generateUrl('dashboard_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                } elseif ($request->query->get('date') == '2Week') {
                    $session->set('startWeek_get', date('Y-m-d', strtotime('monday this week -14 days')));
                    $session->set('endWeek_get', date('Y-m-d', strtotime('sunday this week -14 days')));
                    return $this->redirect($this->generateUrl('dashboard_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                } elseif ($request->query->get('date') == '3Week') {
                    $session->set('startWeek_get', date('Y-m-d', strtotime('monday this week -21 days')));
                    $session->set('endWeek_get', date('Y-m-d', strtotime('sunday this week -21 days')));
                    return $this->redirect($this->generateUrl('dashboard_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                } elseif ($request->query->get('date') == 'thisWeek') {
                    $session->set('startWeek_get', $previous_monday);
                    $session->set('endWeek_get', $next_sunday);
                    return $this->redirect($this->generateUrl('dashboard_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                }
                $data = [];
                $merchantData = $merchantBillingDetailRepository->getProductAndOrder($request->query->get('adminid'),
                    $request->query->get('takeorderby'), $session->get('startWeek_get'), $session->get('endWeek_get'));
                if (!empty($merchantData)) {
                    foreach ($merchantData as $item) {
                        $data[$item['productname']][] = [
                            'path' => $this->cutSyntaxDoublePoint($item['path']),
                            'dayName' => $this->changeDateName($item['dateOnly']),
                            'amounts' => $item['amounts'],
                            'name' => $item['productname']
                        ];
                    }

                    foreach ($data as $key => $val) {
                        $week = [
                            'Monday' => 0,
                            'Tuesday' => 0,
                            'Wednesday' => 0,
                            'Thursday' => 0,
                            'Friday' => 0,
                            'Saturday' => 0,
                            'Sunday' => 0
                        ];
                        $data[$key]['total'] = 0;
                        foreach ($val as $itemVal) {
                            if (array_key_exists($itemVal['dayName'], $week)) {
                                $week[$itemVal['dayName']] = intval($itemVal['amounts']);
                            } else {
                                $week[$itemVal['dayName']] = 0;
                            }
                            $data[$key]['total'] = ($data[$key]['total'] + intval($itemVal['amounts']));
                        }
                        $dataResult[$key] = [
                            'path' => $val[0]['path'],
                            'name' => $val[0]['name'],
                            'week' => $week,
                            'total' => $data[$key]['total']
                        ];
                    }
                    usort($dataResult, function ($item1, $item2) {
                        return $item2['total'] <=> $item1['total'];
                    });


                    $results = $paginator->paginate(
                        $dataResult, /* query NOT result */
                        $request->query->getInt('page', 1)/*page number*/,
                        10
                    );
                } else {
                    $results = [];
                }
                ///////////////////////////////////////////////////////////////////////////////////////////////////////////

                $total1 = $this->getDoctrine()->getRepository(TestSalesComDaily::class)->findOneBy(array('adminid' => $request->query->get('adminid')));
                if ($total1 == null) {
                    $total1 = 0;
                } else {
                    $total1 = $total1->getComAmt();
                }
                $total2 = $this->getDoctrine()->getRepository(TestSalesComMonthly::class)->findOneBy(array('adminid' => $request->query->get('adminid')));
                if ($total2 == null) {
                    $total2 = 0;
                } else {
                    $total2 = $total2->getComAmt();
                }
                $total3 = $this->getDoctrine()->getRepository(TestSalesComTotal::class)->findOneBy(array('adminid' => $request->query->get('adminid')));
                if ($total3 == null) {
                    $total3 = 0;
                } else {
                    $total3 = $total3->getComAmt();
                }
                $total4 = $this->getDoctrine()->getRepository(TestSalesVolumeDaily::class)->findOneBy(array('adminid' => $request->query->get('adminid')));
                if ($total4 == null) {
                    $total4 = 0;
                } else {
                    $total4 = $total4->getComAmt();
                }
                $total5 = $this->getDoctrine()->getRepository(TestSalesVolumeMonthly::class)->findOneBy(array('adminid' => $request->query->get('adminid')));
                if ($total5 == null) {
                    $total5 = 0;
                } else {
                    $total5 = $total5->getComAmt();
                }
                $total6 = $this->getDoctrine()->getRepository(TestSalesVolumeTotal::class)->findOneBy(array('adminid' => $request->query->get('adminid')));
                if ($total6 == null) {
                    $total6 = 0;
                } else {
                    $total6 = $total6->getComAmt();
                }

                $total_result = [
                    't1' => $total1,
                    't2' => $total2,
                    't3' => $total3,
                    't4' => $total4,
                    't5' => $total5,
                    't6' => $total6
                ];

                ///////////////////////////////////////////////////////////////////////////////////////////////////////////

                $thisDate = date("Y-m-d");
                $lastDate = date("Y-m-d", strtotime("-2 month"));
                if (empty($session->get('thisDate_get')) && empty($session->get('lastDate_get'))) {
                    $session->set('thisDate_get', $thisDate);
                    $session->set('lastDate_get', $lastDate);
                }

                if ($request->query->get('date') == 'twoMonth') {
                    $session->set('thisDate_get', date("Y-m-d"));
                    $session->set('lastDate_get', date("Y-m-d", strtotime("-2 month")));
                    return $this->redirect($this->generateUrl('dashboard_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                } elseif ($request->query->get('date') == 'oneMonth') {
                    $session->set('thisDate_get', date("Y-m-d"));
                    $session->set('lastDate_get', date("Y-m-d", strtotime("-1 month")));
                    return $this->redirect($this->generateUrl('dashboard_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                } elseif ($request->query->get('date') == 'fourteen') {
                    $session->set('thisDate_get', date("Y-m-d"));
                    $session->set('lastDate_get', date("Y-m-d", strtotime("-2 week")));
                    return $this->redirect($this->generateUrl('dashboard_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                } elseif ($request->query->get('date') == 'seven') {
                    $session->set('thisDate_get', date("Y-m-d"));
                    $session->set('lastDate_get', date("Y-m-d", strtotime("-1 week")));
                    return $this->redirect($this->generateUrl('dashboard_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                }

                $merchantRawDataChart = $merchantBillingRepository->getDataToChart($request->query->get('takeorderby'),
                    $request->query->get('adminid'), $session->get('thisDate_get'), $session->get('lastDate_get'));
                $listCat = [];
                $list = [];
                $list2 = [];
                $listItem = [];
                $listItem2 = [];
                $xAxis = [];
                foreach ($merchantRawDataChart as $item) {
                    $listItem[$item['orderdate']][] = [
                        'total' => ($item['productprice'] * $item['productorder']) + $item['deliveryFee'],
                        'cat' => $item['productCategories']
                    ];
                    $listItem2[$item['orderdate']][] = [
                        'total' => $item['productCommission'] * $item['productorder'],
                        'cat' => $item['productCategories']
                    ];
                    $listCat[$item['productCategories']] = 0;//สร้างชุดข้อมูล Categories ก่อน
                }

                foreach ($listCat as $key => $val) {
                    if (array_key_exists('ไร่สุพรรณ', $listCat)) {
                        $listCat = array('ไร่สุพรรณ' => 0) + $listCat;
                    }
                    if (array_key_exists('สัตว์เลี้ยง', $listCat)) {
                        $listCat = array('สัตว์เลี้ยง' => 0) + $listCat;
                    }
                    if (array_key_exists('อาหารเสริม', $listCat)) {
                        $listCat = array('อาหารเสริม' => 0) + $listCat;
                    }
                }

                $dataTest = [];
                foreach ($listItem as $key => $value) {
                    foreach ($listCat as $k => $v) {
                        $list[$k] = 0;
                    }
                    foreach ($value as $dataItem) {
                        if (array_key_exists($dataItem['cat'], $list)) {
                            $list[$dataItem['cat']] += $dataItem['total'];
                        } else {
                            $list[$dataItem['cat']] = 0;
                        }
                    }
                    $dataTest[$key] = ['listCat' => $list];
                }
                $dataTest2 = [];

                foreach ($listItem2 as $key => $value) {
                    foreach ($listCat as $k => $v) {
                        $list2[$k] = 0;
                    }
                    foreach ($value as $dataItem) {
                        if (array_key_exists($dataItem['cat'], $list2)) {
                            $list2[$dataItem['cat']] += $dataItem['total'];
                        } else {
                            $list2[$dataItem['cat']] = 0;
                        }
                    }
                    $dataTest2[$key] = ['listCat' => $list2];
                }

                $gValue = [];
                $tValue = [];
                $cValue = [];
                $ctValue = [];
                foreach ($dataTest as $date => $lc) {
                    $xAxis[] = $date;
                    foreach ($lc as $cName => $data) {
                        foreach ($data as $n => $vv) {
                            $gValue[$n][] = $vv;
                        }
                    }
                    $tValue[] = 0;
                }

                foreach ($dataTest2 as $date => $lc) {
                    foreach ($lc as $cName => $data) {
                        foreach ($data as $n => $vv) {
                            $cValue[$n][] = $vv;
                        }
                    }
                    $ctValue[] = 0;
                }

                foreach ($gValue as $key => $val) {
                    foreach ($val as $k => $v) {
                        $tValue[$k] = $tValue[$k] + $gValue[$key][$k];
                    }
                }
                foreach ($cValue as $key => $val) {
                    foreach ($val as $k => $v) {
                        $ctValue[$k] = $ctValue[$k] + $cValue[$key][$k];
                    }
                }

                $chart1[] = [
                    'type' => 'areaspline',
                    'color' => 'rgba(92,184,92,0.73)',
                    'name' => 'ทั้งหมด',
                    'data' => $tValue
                ];
                foreach ($gValue as $key => $val) {
                    $chart1[] = ['type' => 'areaspline', 'name' => $key, 'data' => $gValue[$key]];
                }

                $chart2[] = [
                    'type' => 'areaspline',
                    'color' => 'rgba(92,184,92,0.73)',
                    'name' => 'ทั้งหมด',
                    'data' => $ctValue
                ];
                foreach ($cValue as $key => $val) {
                    $chart2[] = ['type' => 'areaspline', 'name' => $key, 'data' => $cValue[$key]];
                }

                foreach ($chart1 as $key => $val) {
                    if (array_search('อาหารเสริม', $chart1[$key])) {
                        $chart1[$key]['color'] = '#337ab7';
                    }
                    if (array_search('สัตว์เลี้ยง', $chart1[$key])) {
                        $chart1[$key]['color'] = '#f0ad4e';
                    }
                    if (array_search('ไร่สุพรรณ', $chart1[$key])) {
                        $chart1[$key]['color'] = '#9e63ff';
                    }
                }

                foreach ($chart2 as $key => $val) {
                    if (array_search('อาหารเสริม', $chart2[$key])) {
                        $chart2[$key]['color'] = '#337ab7';
                    }
                    if (array_search('สัตว์เลี้ยง', $chart2[$key])) {
                        $chart2[$key]['color'] = '#f0ad4e';
                    }
                    if (array_search('ไร่สุพรรณ', $chart2[$key])) {
                        $chart2[$key]['color'] = '#9e63ff';
                    }
                }

                $ob = new Highchart();
                $ob->chart->renderTo('linechart');
                $ob->title->text('ยอดขายสินค้า');
                $ob->tooltip->shared(true);
                $ob->tooltip->valueSuffix(' บาท');
                $ob->legend->verticalAlign('top');
                $ob->plotOptions->areaspline(array(
                    'fillOpacity' => 0.5
                ));
                $categories = $xAxis;
                $ob->xAxis->categories($categories);
                $ob->yAxis->title(array('text' => 'จำนวนเงิน'));
                $ob->series($chart1);

                ///////////////////////////////////////////////////
                $ob2 = new Highchart();
                $ob2->chart->renderTo('linechart2');
                $ob2->title->text('ยอดคอมมิชชั่น');
                $ob2->tooltip->shared(true);
                $ob2->tooltip->valueSuffix(' บาท');
                $ob2->legend->verticalAlign('top');
                $ob2->plotOptions->areaspline(array(
                    'fillOpacity' => 0.5
                ));
                $categories = $xAxis;
                $ob2->xAxis->categories($categories);
                $ob2->yAxis->min(0);
                $ob2->yAxis->title(array('text' => 'จำนวนเงิน'));
                $ob2->series($chart2);

                /////////////////////////////////////////////////////
                ///////////////////////////////////////////////////
                $ob3 = new Highchart();
                $ob3->chart->renderTo('linechart3');
                $ob3->title->text('ยอดขายรายชั่วโมง');
                $ob3->tooltip->valueSuffix(' บาท');
                $ob3->plotOptions->bar(array(
                    'dataLabels' => array(
                        'enabled' => true
                    )
                ));
                $ob3->legend->verticalAlign('top');
                $ob3->xAxis->categories($hours);
                $ob3->xAxis->title(array(
                    'text' => null
                ));
                $ob3->yAxis->title(array('text' => 'จำนวนเงิน'));
                $ob3->series($chart3);

                return $this->render('dashboard/dashboard_v.html.twig',
                    [
                        'name' => $userData[0],
                        'dateTime' => $dateTime_live,
                        'data' => $results,
                        'data_result' => $total_result,
                        'chart' => $ob,
                        'chart2' => $ob2,
                        'chart3' => $ob3,
                        'status' => 'pass'
                    ]);
            } else {
                return $this->render('dashboard/dashboard_v.html.twig',
                    [
                        'status' => 'fail'
                    ]);
            }
        } else {
            return $this->render('dashboard/dashboard_v.html.twig',
                [
                    'status' => 'fail'
                ]);
        }
    }

    /////////////////////////////////////////////// AFA //////////////////////////////////////////////////////////////

    /**
     * @Route("/dashboard_afa", name="dashboard_afa")
     */
    public
    function dashboard_afa(
        Request $request,
        PaginatorInterface $paginator,
        MerchantBillingRepository $merchantBillingRepository,
        MerchantBillingDetailRepository $merchantBillingDetailRepository
    )
    {
        date_default_timezone_set("Asia/Bangkok");
        $session = new Session();

        $orderDateData = [];
        $orderDateDataHour = [];
        $orderDateRawData = $merchantBillingRepository->getDateByInvoice($this->getUser()->getMerId(),
            $this->getUser()->getId());

        foreach ($orderDateRawData as $key => $val) {
            $orderDateData[$key] = $val['orderdate'];
            $orderDateDataHour[$val['hour']][] = $val;
        }
        $filtered = array_filter(
            $orderDateRawData,
            function ($k) use ($orderDateRawData) {
                return $orderDateRawData[$k]['orderdate']->format('Y-m-d') == date("Y-m-d");
            },
            ARRAY_FILTER_USE_KEY
        );
        $filtered = array_values($filtered);
        $hours = [];
        foreach ($filtered as $key => $val) {
            $filtered[$key]['total'] = ($val['paymentAmt'] + $val['transportprice']) - $val['paymentDiscount'];
        }
        $test6 = [];
        foreach ($filtered as $key => $val) {
            $test6[$val['hour']][] = $val;
        }

        foreach ($test6 as $key => $val) {
            $test6[$key]['total'] = 0;
            foreach ($val as $k => $v) {
                $test6[$key]['total'] = $test6[$key]['total'] + $v['total'];
            }
            $hours[] = $key;
            $test6[$key] = ['' . $key, $test6[$key]['total']];
        }
        $test6 = array_values($test6);
        $chart3[] = ['type' => 'bar', 'color' => '#E75480', 'name' => 'จำนวน', 'data' => $test6];

        $minDate = min($orderDateData);

        $key = array_search($minDate, $orderDateData);

        $dateLowData = $orderDateRawData[$key];
        $dateRaw1 = $dateLowData['orderdate']->format('Y-m-d');
        $dateRaw2 = date("Y-m-d");

        $date1 = strtotime($dateRaw1);
        $date2 = strtotime($dateRaw2);

        $diff = abs($date2 - $date1);

        $years = floor($diff / (365 * 60 * 60 * 24));

        $months = floor(($diff - $years * 365 * 60 * 60 * 24)
            / (30 * 60 * 60 * 24));

        $days = floor(($diff - $years * 365 * 60 * 60 * 24 -
                $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

        $dateTime_live = [
            'years' => $years,
            'months' => $months,
            'days' => $days
        ];
        $previous_monday = date('Y-m-d', strtotime('monday this week'));
        $next_sunday = date('Y-m-d', strtotime('sunday this week'));
        if (empty($session->get('startWeek')) && empty($session->get('endWeek'))) {
            $session->set('startWeek', $previous_monday);
            $session->set('endWeek', $next_sunday);
        }

        if ($request->query->get('date') == 'lastWeek') {
            $session->set('startWeek', date('Y-m-d', strtotime('monday this week -7 days')));
            $session->set('endWeek', date('Y-m-d', strtotime('sunday this week -7 days')));
            return $this->redirect($this->generateUrl('dashboard_afa'));
        } elseif ($request->query->get('date') == '2Week') {
            $session->set('startWeek', date('Y-m-d', strtotime('monday this week -14 days')));
            $session->set('endWeek', date('Y-m-d', strtotime('sunday this week -14 days')));
            return $this->redirect($this->generateUrl('dashboard_afa'));
        } elseif ($request->query->get('date') == '3Week') {
            $session->set('startWeek', date('Y-m-d', strtotime('monday this week -21 days')));
            $session->set('endWeek', date('Y-m-d', strtotime('sunday this week -21 days')));
            return $this->redirect($this->generateUrl('dashboard_afa'));
        } elseif ($request->query->get('date') == 'thisWeek') {
            $session->set('startWeek', $previous_monday);
            $session->set('endWeek', $next_sunday);
            return $this->redirect($this->generateUrl('dashboard_afa'));
        }
        $data = [];
        $merchantData = $merchantBillingDetailRepository->getProductAndOrder($this->getUser()->getId(),
            $this->getUser()->getMerId(), $session->get('startWeek'), $session->get('endWeek'));
        if (!empty($merchantData)) {
            foreach ($merchantData as $item) {
                $data[$item['productname']][] = [
                    'path' => $this->cutSyntaxDoublePoint($item['path']),
                    'dayName' => $this->changeDateName($item['dateOnly']),
                    'amounts' => $item['amounts'],
                    'name' => $item['productname']
                ];
            }

            foreach ($data as $key => $val) {
                $week = [
                    'Monday' => 0,
                    'Tuesday' => 0,
                    'Wednesday' => 0,
                    'Thursday' => 0,
                    'Friday' => 0,
                    'Saturday' => 0,
                    'Sunday' => 0
                ];
                $data[$key]['total'] = 0;
                foreach ($val as $itemVal) {
                    if (array_key_exists($itemVal['dayName'], $week)) {
                        $week[$itemVal['dayName']] = intval($itemVal['amounts']);
                    } else {
                        $week[$itemVal['dayName']] = 0;
                    }
                    $data[$key]['total'] = ($data[$key]['total'] + intval($itemVal['amounts']));
                }
                $dataResult[$key] = [
                    'path' => $val[0]['path'],
                    'name' => $val[0]['name'],
                    'week' => $week,
                    'total' => $data[$key]['total']
                ];
            }
            usort($dataResult, function ($item1, $item2) {
                return $item2['total'] <=> $item1['total'];
            });


            $results = $paginator->paginate(
                $dataResult, /* query NOT result */
                $request->query->getInt('page', 1)/*page number*/,
                10
            );
        } else {
            $results = [];
        }
        ///////////////////////////////////////////////////////////////////////////////////////////////////////////
        $bonus = [
            "total7" => 0,
            "total8" => 0,
            "total9" => 0
        ];
        $total1 = $this->getDoctrine()->getRepository(TestSalesComDaily::class)->findOneBy(array('adminid' => $this->getUser()->getId()));
        if ($total1 == null) {
            $bonus["total7"] = 0;
            $total1 = 0;
        } else {
            $bonus["total7"] = $total1->getComBonus();
            $total1 = $total1->getComAmt();
        }
        $total2 = $this->getDoctrine()->getRepository(TestSalesComMonthly::class)->findOneBy(array('adminid' => $this->getUser()->getId()));
        if ($total2 == null) {
            $bonus["total8"] = 0;
            $total2 = 0;
        } else {
            $bonus["total8"] = $total2->getComBonus();
            $total2 = $total2->getComAmt();
        }
        $total3 = $this->getDoctrine()->getRepository(TestSalesComTotal::class)->findOneBy(array('adminid' => $this->getUser()->getId()));
        if ($total3 == null) {
            $bonus["total9"] = 0;
            $total3 = 0;
        } else {
            $bonus["total9"] = $total3->getComBonus();
            $total3 = $total3->getComAmt();
        }
        $total4 = $this->getDoctrine()->getRepository(TestSalesVolumeDaily::class)->findOneBy(array('adminid' => $this->getUser()->getId()));
        if ($total4 == null) {
            $total4 = 0;
        } else {
            $total4 = $total4->getComAmt();
        }
        $total5 = $this->getDoctrine()->getRepository(TestSalesVolumeMonthly::class)->findOneBy(array('adminid' => $this->getUser()->getId()));
        if ($total5 == null) {
            $total5 = 0;
        } else {
            $total5 = $total5->getComAmt();
        }
        $total6 = $this->getDoctrine()->getRepository(TestSalesVolumeTotal::class)->findOneBy(array('adminid' => $this->getUser()->getId()));
        if ($total6 == null) {
            $total6 = 0;
        } else {
            $total6 = $total6->getComAmt();
        }

        $total_result = [
            't1' => $total1,
            't2' => $total2,
            't3' => $total3,
            't4' => $total4,
            't5' => $total5,
            't6' => $total6,
            't7' => $bonus["total7"],
            't8' => $bonus["total8"],
            't9' => $bonus["total9"],
        ];

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////

        $thisDate = date("Y-m-d");
        $lastDate = date("Y-m-d", strtotime("-2 month"));
        if (empty($session->get('thisDate')) && empty($session->get('lastDate'))) {
            $session->set('thisDate', $thisDate);
            $session->set('lastDate', $lastDate);
        }

        if ($request->query->get('date') == 'twoMonth') {
            $session->set('thisDate', date("Y-m-d"));
            $session->set('lastDate', date("Y-m-d", strtotime("-2 month")));
            return $this->redirect($this->generateUrl('dashboard_afa'));
        } elseif ($request->query->get('date') == 'oneMonth') {
            $session->set('thisDate', date("Y-m-d"));
            $session->set('lastDate', date("Y-m-d", strtotime("-1 month")));
            return $this->redirect($this->generateUrl('dashboard_afa'));
        } elseif ($request->query->get('date') == 'fourteen') {
            $session->set('thisDate', date("Y-m-d"));
            $session->set('lastDate', date("Y-m-d", strtotime("-2 week")));
            return $this->redirect($this->generateUrl('dashboard_afa'));
        } elseif ($request->query->get('date') == 'seven') {
            $session->set('thisDate', date("Y-m-d"));
            $session->set('lastDate', date("Y-m-d", strtotime("-1 week")));
            return $this->redirect($this->generateUrl('dashboard_afa'));
        }

        $merchantRawDataChart = $merchantBillingRepository->getDataAFAToChart($this->getUser()->getMerId(),
            $this->getUser()->getId(), $session->get('thisDate'), $session->get('lastDate'));;
        $listCat = [];
        $list = [];
        $list1 = [];
        $list2 = [];
        $listItem = [];
        $listItem2 = [];
        $xAxis = [];
        foreach ($merchantRawDataChart as $item) {
            $listItem[$item['orderdate']][] = [
                'total' => ($item['productprice'] * $item['productorder']) + $item['deliveryFee'],
                'cat' => $item['productCategories']
            ];
            $listItem1[$item['orderdate']][] = [
                'total' => $item['afaCommissionValue'] * $item['productorder'],
                'cat' => $item['productCategories']
            ];
            $listItem2[$item['orderdate']][] = [
                'total' => $item['afaCommissionBonus'] * $item['productorder'],
                'cat' => $item['productCategories']
            ];
            $listCat[$item['productCategories']] = 0;//สร้างชุดข้อมูล Categories ก่อน
        }

        foreach ($listCat as $key => $val) {
            if (array_key_exists('ไร่สุพรรณ', $listCat)) {
                $listCat = array('ไร่สุพรรณ' => 0) + $listCat;
            }
            if (array_key_exists('สัตว์เลี้ยง', $listCat)) {
                $listCat = array('สัตว์เลี้ยง' => 0) + $listCat;
            }
            if (array_key_exists('อาหารเสริม', $listCat)) {
                $listCat = array('อาหารเสริม' => 0) + $listCat;
            }
        }

        $dataTest = [];
        $dataTest1 = [];
        $dataTest2 = [];
        foreach ($listItem as $key => $value) {
            foreach ($listCat as $k => $v) {
                $list[$k] = 0;
            }
            foreach ($value as $dataItem) {
                if (array_key_exists($dataItem['cat'], $list)) {
                    $list[$dataItem['cat']] += $dataItem['total'];
                } else {
                    $list[$dataItem['cat']] = 0;
                }
            }
            $dataTest[$key] = ['listCat' => $list];
        }

        foreach ($listItem1 as $key => $value) {
            foreach ($listCat as $k => $v) {
                $list1[$k] = 0;
            }
            foreach ($value as $dataItem) {
                if (array_key_exists($dataItem['cat'], $list1)) {
                    $list1[$dataItem['cat']] += $dataItem['total'];
                } else {
                    $list1[$dataItem['cat']] = 0;
                }
            }
            $dataTest1[$key] = ['listCat' => $list1];
        }

        foreach ($listItem2 as $key => $value) {
            foreach ($listCat as $k => $v) {
                $list2[$k] = 0;
            }
            foreach ($value as $dataItem) {
                if (array_key_exists($dataItem['cat'], $list2)) {
                    $list2[$dataItem['cat']] += $dataItem['total'];
                } else {
                    $list2[$dataItem['cat']] = 0;
                }
            }
            $dataTest2[$key] = ['listCat' => $list2];
        }

        $gValue = [];
        $bValue = [];
        $tValue = [];
        $btValue = [];
        $cValue = [];
        $ctValue = [];

        foreach ($dataTest as $date => $lc) {
            $xAxis[] = $date;
            foreach ($lc as $cName => $data) {
                foreach ($data as $n => $vv) {
                    $gValue[$n][] = $vv;
                }
            }
            $tValue[] = 0;
        }

        foreach ($dataTest1 as $date => $lc) {
            foreach ($lc as $cName => $data) {
                foreach ($data as $n => $vv) {
                    $bValue[$n][] = $vv;
                }
            }
            $btValue[] = 0;
        }

        foreach ($dataTest2 as $date => $lc) {
            foreach ($lc as $cName => $data) {
                foreach ($data as $n => $vv) {
                    $cValue[$n][] = $vv;
                }
            }
            $ctValue[] = 0;
        }

        foreach ($gValue as $key => $val) {
            foreach ($val as $k => $v) {
                $tValue[$k] = $tValue[$k] + $gValue[$key][$k];
            }
        }

        foreach ($bValue as $key => $val) {
            foreach ($val as $k => $v) {
                $btValue[$k] = $btValue[$k] + $bValue[$key][$k];
            }
        }

        foreach ($cValue as $key => $val) {
            foreach ($val as $k => $v) {
                $ctValue[$k] = $ctValue[$k] + $cValue[$key][$k];
            }
        }

        $chart[] = ['type' => 'areaspline', 'color' => 'rgba(92,184,92,0.73)', 'name' => 'ทั้งหมด', 'data' => $tValue];


        $chart1[] = [
            'type' => 'areaspline',
            'color' => 'rgba(92,184,92,0.73)',
            'name' => 'ทั้งหมด',
            'data' => $btValue
        ];

        $chart2[] = [
            'type' => 'areaspline',
            'color' => 'rgba(92,184,92,0.73)',
            'name' => 'ทั้งหมด',
            'data' => $ctValue
        ];
        foreach ($gValue as $key => $val) {
            $chart[] = ['type' => 'areaspline', 'name' => $key, 'data' => $gValue[$key]];
        }

        foreach ($bValue as $key => $val) {
            $chart1[] = ['type' => 'areaspline', 'name' => $key, 'data' => $bValue[$key]];
        }

        foreach ($cValue as $key => $val) {
            $chart2[] = ['type' => 'areaspline', 'name' => $key, 'data' => $cValue[$key]];
        }

        foreach ($chart as $key => $val) {
            if (array_search('อาหารเสริม', $chart[$key])) {
                $chart[$key]['color'] = '#337ab7';
            }
            if (array_search('สัตว์เลี้ยง', $chart[$key])) {
                $chart[$key]['color'] = '#f0ad4e';
            }
            if (array_search('ไร่สุพรรณ', $chart[$key])) {
                $chart[$key]['color'] = '#9e63ff';
            }
        }

        foreach ($chart1 as $key => $val) {
            if (array_search('อาหารเสริม', $chart1[$key])) {
                $chart1[$key]['color'] = '#337ab7';
            }
            if (array_search('สัตว์เลี้ยง', $chart1[$key])) {
                $chart1[$key]['color'] = '#f0ad4e';
            }
            if (array_search('ไร่สุพรรณ', $chart1[$key])) {
                $chart1[$key]['color'] = '#9e63ff';
            }
        }

        foreach ($chart2 as $key => $val) {
            if (array_search('อาหารเสริม', $chart2[$key])) {
                $chart2[$key]['color'] = '#337ab7';
            }
            if (array_search('สัตว์เลี้ยง', $chart2[$key])) {
                $chart2[$key]['color'] = '#f0ad4e';
            }
            if (array_search('ไร่สุพรรณ', $chart2[$key])) {
                $chart2[$key]['color'] = '#9e63ff';
            }
        }

        $ob = new Highchart();
        $ob->chart->renderTo('linechart');
        $ob->title->text('ยอดขายสินค้า');
        $ob->tooltip->shared(true);
        $ob->tooltip->valueSuffix(' บาท');
        $ob->legend->verticalAlign('top');
        $ob->plotOptions->areaspline(array(
            'fillOpacity' => 0.5
        ));
        $categories = $xAxis;
        $ob->xAxis->categories($categories);
        $ob->yAxis->title(array('text' => 'จำนวนเงิน'));
        $ob->series($chart);

        ///////////////////////////////////////////////////
        $ob1 = new Highchart();
        $ob1->chart->renderTo('linechart1');
        $ob1->title->text('ยอดคอมมิชชั่น');
        $ob1->tooltip->shared(true);
        $ob1->tooltip->valueSuffix(' บาท');
        $ob1->legend->verticalAlign('top');
        $ob1->plotOptions->areaspline(array(
            'fillOpacity' => 0.5
        ));
        $categories = $xAxis;
        $ob1->xAxis->categories($categories);
        $ob1->yAxis->title(array('text' => 'จำนวนเงิน'));
        $ob1->series($chart1);

        ///////////////////////////////////////////////////
        $ob2 = new Highchart();
        $ob2->chart->renderTo('linechart2');
        $ob2->title->text('ยอดโบนัส');
        $ob2->tooltip->shared(true);
        $ob2->tooltip->valueSuffix(' บาท');
        $ob2->legend->verticalAlign('top');
        $ob2->plotOptions->areaspline(array(
            'fillOpacity' => 0.5
        ));
        $categories = $xAxis;
        $ob2->xAxis->categories($categories);
        $ob2->yAxis->min(0);
        $ob2->yAxis->title(array('text' => 'จำนวนเงิน'));
        $ob2->series($chart2);

        /////////////////////////////////////////////////////
        ///////////////////////////////////////////////////
        $ob3 = new Highchart();
        $ob3->chart->renderTo('linechart3');
        $ob3->title->text('ยอดขายรายชั่วโมง');
        $ob3->tooltip->valueSuffix(' บาท');
        $ob3->plotOptions->bar(array(
            'dataLabels' => array(
                'enabled' => true
            )
        ));
        $ob3->legend->verticalAlign('top');
        $ob3->xAxis->categories($hours);
        $ob3->xAxis->title(array(
            'text' => null
        ));
        $ob3->yAxis->title(array('text' => 'จำนวนเงิน'));
        $ob3->series($chart3);

        return $this->render('dashboard/dashboard_afa.html.twig',
            [
                'dateTime' => $dateTime_live,
                'data' => $results,
                'data_result' => $total_result,
                'chart' => $ob,
                'chart1' => $ob1,
                'chart2' => $ob2,
                'chart3' => $ob3
            ]);
    }

    /**
     * @Route("/dashboard_afa_get", name="dashboard_afa_get")
     */
    public function dashboard_afa_get(
        Request $request,
        PaginatorInterface $paginator,
        MerchantBillingRepository $merchantBillingRepository,
        MerchantBillingDetailRepository $merchantBillingDetailRepository,
        GlobalAuthenRepository $authenRepository
    )
    {
        date_default_timezone_set("Asia/Bangkok");
        $session = new Session();

        $orderDateData = [];
        $orderDateDataHour = [];
        if ($request->query->get('adminid') != null && $request->query->get('takeorderby') != null) {
            $orderDateRawData = $merchantBillingRepository->getDateByInvoice($request->query->get('takeorderby'),
                $request->query->get('adminid'));
            $userData = $authenRepository->getUserData($request->query->get('adminid'));
            foreach ($userData as $key => $val) {
                foreach ($val as $k => $v) {
                    if ($k === 'phoneno') {
                        $userData[$key]['phoneno'] = $this->doubleSix2Zero($v);
                    }
                }
            }
            if (!empty($orderDateRawData)) {
                foreach ($orderDateRawData as $key => $val) {
                    $orderDateData[$key] = $val['orderdate'];
                    $orderDateDataHour[$val['hour']][] = $val;
                }
                $filtered = array_filter(
                    $orderDateRawData,
                    function ($k) use ($orderDateRawData) {
                        return $orderDateRawData[$k]['orderdate']->format('Y-m-d') == date("Y-m-d");
                    },
                    ARRAY_FILTER_USE_KEY
                );
                $filtered = array_values($filtered);
                $hours = [];
                foreach ($filtered as $key => $val) {
                    $filtered[$key]['total'] = ($val['paymentAmt'] + $val['transportprice']) - $val['paymentDiscount'];
                }
                $test6 = [];
                foreach ($filtered as $key => $val) {
                    $test6[$val['hour']][] = $val;
                }

                foreach ($test6 as $key => $val) {
                    $test6[$key]['total'] = 0;
                    foreach ($val as $k => $v) {
                        $test6[$key]['total'] = $test6[$key]['total'] + $v['total'];
                    }
                    $hours[] = $key;
                    $test6[$key] = ['' . $key, $test6[$key]['total']];
                }
                $test6 = array_values($test6);
                $chart3[] = ['type' => 'bar', 'color' => '#E75480', 'name' => 'จำนวน', 'data' => $test6];

                $minDate = min($orderDateData);

                $key = array_search($minDate, $orderDateData);

                $dateLowData = $orderDateRawData[$key];
                $dateRaw1 = $dateLowData['orderdate']->format('Y-m-d');
                $dateRaw2 = date("Y-m-d");

                $date1 = strtotime($dateRaw1);
                $date2 = strtotime($dateRaw2);

                $diff = abs($date2 - $date1);

                $years = floor($diff / (365 * 60 * 60 * 24));

                $months = floor(($diff - $years * 365 * 60 * 60 * 24)
                    / (30 * 60 * 60 * 24));

                $days = floor(($diff - $years * 365 * 60 * 60 * 24 -
                        $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

                $dateTime_live = [
                    'years' => $years,
                    'months' => $months,
                    'days' => $days
                ];
                $previous_monday = date('Y-m-d', strtotime('monday this week'));
                $next_sunday = date('Y-m-d', strtotime('sunday this week'));
                if (empty($session->get('startWeek_get')) && empty($session->get('endWeek_get'))) {
                    $session->set('startWeek_get', $previous_monday);
                    $session->set('endWeek_get', $next_sunday);
                }
                if ($request->query->get('date') == 'lastWeek') {
                    $session->set('startWeek_get', date('Y-m-d', strtotime('monday this week -7 days')));
                    $session->set('endWeek_get', date('Y-m-d', strtotime('sunday this week -7 days')));
                    return $this->redirect($this->generateUrl('dashboard_afa_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                } elseif ($request->query->get('date') == '2Week') {
                    $session->set('startWeek_get', date('Y-m-d', strtotime('monday this week -14 days')));
                    $session->set('endWeek_get', date('Y-m-d', strtotime('sunday this week -14 days')));
                    return $this->redirect($this->generateUrl('dashboard_afa_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                } elseif ($request->query->get('date') == '3Week') {
                    $session->set('startWeek_get', date('Y-m-d', strtotime('monday this week -21 days')));
                    $session->set('endWeek_get', date('Y-m-d', strtotime('sunday this week -21 days')));
                    return $this->redirect($this->generateUrl('dashboard_afa_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                } elseif ($request->query->get('date') == 'thisWeek') {
                    $session->set('startWeek_get', $previous_monday);
                    $session->set('endWeek_get', $next_sunday);
                    return $this->redirect($this->generateUrl('dashboard_afa_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                }
                $data = [];
                $merchantData = $merchantBillingDetailRepository->getProductAndOrder($request->query->get('adminid'),
                    $request->query->get('takeorderby'), $session->get('startWeek_get'), $session->get('endWeek_get'));
                if (!empty($merchantData)) {
                    foreach ($merchantData as $item) {
                        $data[$item['productname']][] = [
                            'path' => $this->cutSyntaxDoublePoint($item['path']),
                            'dayName' => $this->changeDateName($item['dateOnly']),
                            'amounts' => $item['amounts'],
                            'name' => $item['productname']
                        ];
                    }

                    foreach ($data as $key => $val) {
                        $week = [
                            'Monday' => 0,
                            'Tuesday' => 0,
                            'Wednesday' => 0,
                            'Thursday' => 0,
                            'Friday' => 0,
                            'Saturday' => 0,
                            'Sunday' => 0
                        ];
                        $data[$key]['total'] = 0;
                        foreach ($val as $itemVal) {
                            if (array_key_exists($itemVal['dayName'], $week)) {
                                $week[$itemVal['dayName']] = intval($itemVal['amounts']);
                            } else {
                                $week[$itemVal['dayName']] = 0;
                            }
                            $data[$key]['total'] = ($data[$key]['total'] + intval($itemVal['amounts']));
                        }
                        $dataResult[$key] = [
                            'path' => $val[0]['path'],
                            'name' => $val[0]['name'],
                            'week' => $week,
                            'total' => $data[$key]['total']
                        ];
                    }
                    usort($dataResult, function ($item1, $item2) {
                        return $item2['total'] <=> $item1['total'];
                    });


                    $results = $paginator->paginate(
                        $dataResult, /* query NOT result */
                        $request->query->getInt('page', 1)/*page number*/,
                        10
                    );
                } else {
                    $session->remove('startWeek_get');
                    $session->remove('endWeek_get');
                    $results = [];
//                    return $this->redirect($this->generateUrl('dashboard_afa_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                }
                ///////////////////////////////////////////////////////////////////////////////////////////////////////////

                $bonus = [
                    "total7" => 0,
                    "total8" => 0,
                    "total9" => 0
                ];

                $total1 = $this->getDoctrine()->getRepository(TestSalesComDaily::class)->findOneBy(array('adminid' => $request->query->get('adminid')));
                if ($total1 == null) {
                    $bonus["total7"] = 0;
                    $total1 = 0;
                } else {
                    $bonus["total7"] = $total1->getComBonus();
                    $total1 = $total1->getComAmt();
                }
                $total2 = $this->getDoctrine()->getRepository(TestSalesComMonthly::class)->findOneBy(array('adminid' => $request->query->get('adminid')));
                if ($total2 == null) {
                    $bonus["total8"] = 0;
                    $total2 = 0;
                } else {
                    $bonus["total8"] = $total2->getComBonus();
                    $total2 = $total2->getComAmt();
                }
                $total3 = $this->getDoctrine()->getRepository(TestSalesComTotal::class)->findOneBy(array('adminid' => $request->query->get('adminid')));
                if ($total3 == null) {
                    $bonus["total9"] = 0;
                    $total3 = 0;
                } else {
                    $bonus["total9"] = $total3->getComBonus();
                    $total3 = $total3->getComAmt();
                }
                $total4 = $this->getDoctrine()->getRepository(TestSalesVolumeDaily::class)->findOneBy(array('adminid' => $request->query->get('adminid')));
                if ($total4 == null) {
                    $total4 = 0;
                } else {
                    $total4 = $total4->getComAmt();
                }
                $total5 = $this->getDoctrine()->getRepository(TestSalesVolumeMonthly::class)->findOneBy(array('adminid' => $request->query->get('adminid')));
                if ($total5 == null) {
                    $total5 = 0;
                } else {
                    $total5 = $total5->getComAmt();
                }
                $total6 = $this->getDoctrine()->getRepository(TestSalesVolumeTotal::class)->findOneBy(array('adminid' => $request->query->get('adminid')));
                if ($total6 == null) {
                    $total6 = 0;
                } else {
                    $total6 = $total6->getComAmt();
                }

                $total_result = [
                    't1' => $total1,
                    't2' => $total2,
                    't3' => $total3,
                    't4' => $total4,
                    't5' => $total5,
                    't6' => $total6,
                    't7' => $bonus["total7"],
                    't8' => $bonus["total8"],
                    't9' => $bonus["total9"],
                ];

                ///////////////////////////////////////////////////////////////////////////////////////////////////////////

                $thisDate = date("Y-m-d");
                $lastDate = date("Y-m-d", strtotime("-2 month"));
                if (empty($session->get('thisDate_get')) && empty($session->get('lastDate_get'))) {
                    $session->set('thisDate_get', $thisDate);
                    $session->set('lastDate_get', $lastDate);
                }

                if ($request->query->get('date') == 'twoMonth') {
                    $session->set('thisDate_get', date("Y-m-d"));
                    $session->set('lastDate_get', date("Y-m-d", strtotime("-2 month")));
                    return $this->redirect($this->generateUrl('dashboard_afa_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                } elseif ($request->query->get('date') == 'oneMonth') {
                    $session->set('thisDate_get', date("Y-m-d"));
                    $session->set('lastDate_get', date("Y-m-d", strtotime("-1 month")));
                    return $this->redirect($this->generateUrl('dashboard_afa_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                } elseif ($request->query->get('date') == 'fourteen') {
                    $session->set('thisDate_get', date("Y-m-d"));
                    $session->set('lastDate_get', date("Y-m-d", strtotime("-2 week")));
                    return $this->redirect($this->generateUrl('dashboard_afa_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                } elseif ($request->query->get('date') == 'seven') {
                    $session->set('thisDate_get', date("Y-m-d"));
                    $session->set('lastDate_get', date("Y-m-d", strtotime("-1 week")));
                    return $this->redirect($this->generateUrl('dashboard_afa_get') . '?takeorderby=' . $request->query->get('takeorderby') . '&adminid=' . $request->query->get('adminid'));
                }

                $merchantRawDataChart = $merchantBillingRepository->getDataAFAToChart($request->query->get('takeorderby'),
                    $request->query->get('adminid'), $session->get('thisDate_get'), $session->get('lastDate_get'));
                $listCat = [];
                $list = [];
                $list1 = [];
                $list2 = [];
                $listItem = [];
                $listItem2 = [];
                $xAxis = [];
                foreach ($merchantRawDataChart as $item) {
                    $listItem[$item['orderdate']][] = [
                        'total' => ($item['productprice'] * $item['productorder']) + $item['deliveryFee'],
                        'cat' => $item['productCategories']
                    ];
                    $listItem1[$item['orderdate']][] = [
                        'total' => $item['afaCommissionValue'] * $item['productorder'],
                        'cat' => $item['productCategories']
                    ];
                    $listItem2[$item['orderdate']][] = [
                        'total' => $item['afaCommissionBonus'] * $item['productorder'],
                        'cat' => $item['productCategories']
                    ];
                    $listCat[$item['productCategories']] = 0;//สร้างชุดข้อมูล Categories ก่อน
                }

                foreach ($listCat as $key => $val) {
                    if (array_key_exists('ไร่สุพรรณ', $listCat)) {
                        $listCat = array('ไร่สุพรรณ' => 0) + $listCat;
                    }
                    if (array_key_exists('สัตว์เลี้ยง', $listCat)) {
                        $listCat = array('สัตว์เลี้ยง' => 0) + $listCat;
                    }
                    if (array_key_exists('อาหารเสริม', $listCat)) {
                        $listCat = array('อาหารเสริม' => 0) + $listCat;
                    }
                }

                $dataTest = [];
                $dataTest1 = [];
                $dataTest2 = [];
                foreach ($listItem as $key => $value) {
                    foreach ($listCat as $k => $v) {
                        $list[$k] = 0;
                    }
                    foreach ($value as $dataItem) {
                        if (array_key_exists($dataItem['cat'], $list)) {
                            $list[$dataItem['cat']] += $dataItem['total'];
                        } else {
                            $list[$dataItem['cat']] = 0;
                        }
                    }
                    $dataTest[$key] = ['listCat' => $list];
                }

                foreach ($listItem1 as $key => $value) {
                    foreach ($listCat as $k => $v) {
                        $list1[$k] = 0;
                    }
                    foreach ($value as $dataItem) {
                        if (array_key_exists($dataItem['cat'], $list1)) {
                            $list1[$dataItem['cat']] += $dataItem['total'];
                        } else {
                            $list1[$dataItem['cat']] = 0;
                        }
                    }
                    $dataTest1[$key] = ['listCat' => $list1];
                }

                foreach ($listItem2 as $key => $value) {
                    foreach ($listCat as $k => $v) {
                        $list2[$k] = 0;
                    }
                    foreach ($value as $dataItem) {
                        if (array_key_exists($dataItem['cat'], $list2)) {
                            $list2[$dataItem['cat']] += $dataItem['total'];
                        } else {
                            $list2[$dataItem['cat']] = 0;
                        }
                    }
                    $dataTest2[$key] = ['listCat' => $list2];
                }

                $gValue = [];
                $tValue = [];
                $cValue = [];
                $ctValue = [];
                $bValue = [];
                $btValue = [];
                foreach ($dataTest as $date => $lc) {
                    $xAxis[] = $date;
                    foreach ($lc as $cName => $data) {
                        foreach ($data as $n => $vv) {
                            $gValue[$n][] = $vv;
                        }
                    }
                    $tValue[] = 0;
                }

                foreach ($dataTest1 as $date => $lc) {
                    $xAxis[] = $date;
                    foreach ($lc as $cName => $data) {
                        foreach ($data as $n => $vv) {
                            $bValue[$n][] = $vv;
                        }
                    }
                    $btValue[] = 0;
                }

                foreach ($dataTest2 as $date => $lc) {
                    foreach ($lc as $cName => $data) {
                        foreach ($data as $n => $vv) {
                            $cValue[$n][] = $vv;
                        }
                    }
                    $ctValue[] = 0;
                }

                foreach ($gValue as $key => $val) {
                    foreach ($val as $k => $v) {
                        $tValue[$k] = $tValue[$k] + $gValue[$key][$k];
                    }
                }

                foreach ($bValue as $key => $val) {
                    foreach ($val as $k => $v) {
                        $btValue[$k] = $btValue[$k] + $bValue[$key][$k];
                    }
                }

                foreach ($cValue as $key => $val) {
                    foreach ($val as $k => $v) {
                        $ctValue[$k] = $ctValue[$k] + $cValue[$key][$k];
                    }
                }

                $chart[] = [
                    'type' => 'areaspline',
                    'color' => 'rgba(92,184,92,0.73)',
                    'name' => 'ทั้งหมด',
                    'data' => $tValue
                ];
                foreach ($gValue as $key => $val) {
                    $chart[] = ['type' => 'areaspline', 'name' => $key, 'data' => $gValue[$key]];
                }

                $chart1[] = [
                    'type' => 'areaspline',
                    'color' => 'rgba(92,184,92,0.73)',
                    'name' => 'ทั้งหมด',
                    'data' => $btValue
                ];
                foreach ($bValue as $key => $val) {
                    $chart1[] = ['type' => 'areaspline', 'name' => $key, 'data' => $bValue[$key]];
                }

                $chart2[] = [
                    'type' => 'areaspline',
                    'color' => 'rgba(92,184,92,0.73)',
                    'name' => 'ทั้งหมด',
                    'data' => $ctValue
                ];
                foreach ($cValue as $key => $val) {
                    $chart2[] = ['type' => 'areaspline', 'name' => $key, 'data' => $cValue[$key]];
                }

                foreach ($chart as $key => $val) {
                    if (array_search('อาหารเสริม', $chart[$key])) {
                        $chart[$key]['color'] = '#337ab7';
                    }
                    if (array_search('สัตว์เลี้ยง', $chart[$key])) {
                        $chart[$key]['color'] = '#f0ad4e';
                    }
                    if (array_search('ไร่สุพรรณ', $chart[$key])) {
                        $chart[$key]['color'] = '#9e63ff';
                    }
                }

                foreach ($chart1 as $key => $val) {
                    if (array_search('อาหารเสริม', $chart1[$key])) {
                        $chart1[$key]['color'] = '#337ab7';
                    }
                    if (array_search('สัตว์เลี้ยง', $chart1[$key])) {
                        $chart1[$key]['color'] = '#f0ad4e';
                    }
                    if (array_search('ไร่สุพรรณ', $chart1[$key])) {
                        $chart1[$key]['color'] = '#9e63ff';
                    }
                }

                foreach ($chart2 as $key => $val) {
                    if (array_search('อาหารเสริม', $chart2[$key])) {
                        $chart2[$key]['color'] = '#337ab7';
                    }
                    if (array_search('สัตว์เลี้ยง', $chart2[$key])) {
                        $chart2[$key]['color'] = '#f0ad4e';
                    }
                    if (array_search('ไร่สุพรรณ', $chart2[$key])) {
                        $chart2[$key]['color'] = '#9e63ff';
                    }
                }

                $ob = new Highchart();
                $ob->chart->renderTo('linechart');
                $ob->title->text('ยอดขายสินค้า');
                $ob->tooltip->shared(true);
                $ob->tooltip->valueSuffix(' บาท');
                $ob->legend->verticalAlign('top');
                $ob->plotOptions->areaspline(array(
                    'fillOpacity' => 0.5
                ));
                $categories = $xAxis;
                $ob->xAxis->categories($categories);
                $ob->yAxis->title(array('text' => 'จำนวนเงิน'));
                $ob->series($chart);

                ///////////////////////////////////////////////////

                $ob1 = new Highchart();
                $ob1->chart->renderTo('linechart1');
                $ob1->title->text('ยอดคอมมิชชั่น');
                $ob1->tooltip->shared(true);
                $ob1->tooltip->valueSuffix(' บาท');
                $ob1->legend->verticalAlign('top');
                $ob1->plotOptions->areaspline(array(
                    'fillOpacity' => 0.5
                ));
                $categories = $xAxis;
                $ob1->xAxis->categories($categories);
                $ob1->yAxis->title(array('text' => 'จำนวนเงิน'));
                $ob1->series($chart1);

                ///////////////////////////////////////////////////
                $ob2 = new Highchart();
                $ob2->chart->renderTo('linechart2');
                $ob2->title->text('ยอดโบนัส');
                $ob2->tooltip->shared(true);
                $ob2->tooltip->valueSuffix(' บาท');
                $ob2->legend->verticalAlign('top');
                $ob2->plotOptions->areaspline(array(
                    'fillOpacity' => 0.5
                ));
                $categories = $xAxis;
                $ob2->xAxis->categories($categories);
                $ob2->yAxis->min(0);
                $ob2->yAxis->title(array('text' => 'จำนวนเงิน'));
                $ob2->series($chart2);

                /////////////////////////////////////////////////////
                ///////////////////////////////////////////////////
                $ob3 = new Highchart();
                $ob3->chart->renderTo('linechart3');
                $ob3->title->text('ยอดขายรายชั่วโมง');
                $ob3->tooltip->valueSuffix(' บาท');
                $ob3->plotOptions->bar(array(
                    'dataLabels' => array(
                        'enabled' => true
                    )
                ));
                $ob3->legend->verticalAlign('top');
                $ob3->xAxis->categories($hours);
                $ob3->xAxis->title(array(
                    'text' => null
                ));
                $ob3->yAxis->title(array('text' => 'จำนวนเงิน'));
                $ob3->series($chart3);

                return $this->render('dashboard/dashboard_afa_v.html.twig',
                    [
                        'name' => $userData[0],
                        'dateTime' => $dateTime_live,
                        'data' => $results,
                        'data_result' => $total_result,
                        'chart' => $ob,
                        'chart1' => $ob1,
                        'chart2' => $ob2,
                        'chart3' => $ob3,
                        'status' => 'pass'
                    ]);
            } else {
                return $this->render('dashboard/dashboard_afa_v.html.twig',
                    [
                        'status' => 'fail'
                    ]);
            }
        } else {
            return $this->render('dashboard/dashboard_afa_v.html.twig',
                [
                    'status' => 'fail'
                ]);
        }
    }

    public function doubleSix2Zero($phoneNO)
    {
        $pattern = '/^66\d{9}$/';
        $phoneNO = trim($phoneNO);
        if (preg_match($pattern, $phoneNO)) {
            $arr = str_split($phoneNO);
            if (isset($arr[0], $arr[1])) {
                if ($arr[0] . $arr[1] == '66') {
                    $phoneNO = '0';
                    for ($i = 2; $i < count($arr); $i++) {
                        $phoneNO .= $arr[$i];
                    }
                }
            }
        }
        return $phoneNO;
    }

    function changeDateName($date)
    {
        $date2 = date('l', strtotime($date));
        return $date2;
    }

    function cutSyntaxDoublePoint($word)
    {
        $word = trim($word);
        $word2 = explode("..", $word);
        return $word2[0] . $word2[1];
    }

}
