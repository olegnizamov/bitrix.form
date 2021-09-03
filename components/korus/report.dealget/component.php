<?

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!CModule::IncludeModule('crm')) {
    ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
    return;
}
global $APPLICATION;

if ($_GET['del_filter_company_search'] == 'Отмена') {
    unset($_GET);
}

function excludeHolidays(DateTime $start, DateTime $end, int $time = 0)
{
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);

    $currentYear = date("Y");
    preg_match_all(
        "/((?P<day>[1-3]?\d{1}).(?P<month>[01]?\d{1}),?)/",
        Bitrix\Main\Config\Option::get("calendar", "year_holidays", ""),
        $matches,
        PREG_SET_ORDER
    );

    $holidays = array_map(
        function ($match) use ($currentYear) {
            return date('Y-m-d', strtotime("$currentYear-{$match["month"]}-{$match["day"]}"));
        },
        $matches
    );


    foreach ($period as $dt) {
        $curr = $dt->format('D');
        if (($curr == 'Sat' || $curr == 'Sun')
            || (in_array($dt->format('Y-m-d'), $holidays))) {
            $resultTime = ($time - 1440 <= 0) ? 0 : ($time - 1440);
            $time = $resultTime;
        }
    }

    return $time;
}

$excelView = $_GET['export'];
$run = false;
$FILTER = [];
$TIME_FILTER = 0;

$filterArray = [
    'DATE_CREATE_FROM' => '>=DATE_CREATE',
    'DATE_CREATE_TO'   => '<=DATE_CREATE',
    'BEGINDATE_FROM'   => '>=BEGINDATE',
    'BEGINDATE_TO'     => '<=BEGINDATE',
];

foreach ($filterArray as $parameter => $filter) {
    if (!empty($_GET[$parameter])) {
        $FILTER[$filter] = $_GET[$parameter];
        $run = true;
    }
}

if (!empty($_GET['HOUR'])) {
    $TIME_FILTER = (int)$_GET['HOUR'] * 60;
}

if (!empty($_GET['MINUTES'])) {
    $TIME_FILTER += (int)$_GET['MINUTES'];
}

$stages = CCrmDeal::GetStages();

$deals = CCrmDeal::GetList([], $FILTER);
$category = \Bitrix\Crm\Category\DealCategory::getList([]);

$rows = [
    'TITLE'       => ['head' => GetMessage('TITLE'), 'data' => ''],
    'DATE_CREATE' => ['head' => GetMessage('DATE_CREATE'), 'data' => ''],
    'BEGINDATE'   => ['head' => GetMessage('BEGINDATE'), 'data' => ''],
    'DIRECTION'   => ['head' => GetMessage('DIRECTION'), 'data' => ''],
];

$timeStages = [];

foreach ($stages as $status_id => $staqgesData) {
    $arResult['DIRECTION']['Общее'][$status_id] = $staqgesData['NAME'];
    $tableStage[$staqgesData['NAME']] = ['head' => $staqgesData['NAME'], 'data' => ''];
    $timeStagesTable[$staqgesData['NAME']] = [
        'time'   => 0,
        'count'  => 0,
        'middle' => '',
        'title'  => $staqgesData['NAME'],
    ];
    $stagesArray[$status_id] = $staqgesData['NAME'];
}


while ($cat = $category->fetch()) {
    $direction = \Bitrix\Crm\Category\DealCategory::getStageList($cat['ID']);
    $explode = explode(':', key($direction));
    $arResult['VIEW_DIRECTION'][$explode[0]] = $cat['NAME'];
    $arResult['DIRECTION'][$cat['NAME']] = $direction;

    foreach ($direction as $stageId => $stage) {
        $tableStage[$stage] = ['head' => $stage, 'data' => ''];
        $stagesArray[$stageId] = $stage;
        $timeStagesTable[$stage] = ['time' => 0, 'count' => 0, 'middle' => '', 'title' => $stage];
    }
}

if (!empty($_GET['STAGES'])) {
    foreach ($_GET['STAGES'] as $index => $stage) {
        $rows[$stagesArray[$stage]] = ['head' => $stagesArray[$stage], 'data' => ''];
        $timeStages[$stagesArray[$stage]] = [
            'time'   => 0,
            'count'  => 0,
            'middle' => '',
            'title'  => $stagesArray[$stage],
        ];
    }
} else {
    $rows += $tableStage;
    $timeStages += $timeStagesTable;
}

if ($run) {
    while ($deal = $deals->fetch()) {
        foreach ($rows as $key => $val) {
            $rows[$key]['data'] = $deal[$key];
        }

        foreach ($arResult['DIRECTION'] as $direactionName => $directionData) {
            if (!empty($directionData[$deal['STAGE_ID']])) {
                $rows['DIRECTION']['data'] = $direactionName;
            }
        }

        $stopIteration = false;

        $events = CCrmEvent::GetListEx(
            [],
            ['ENTITY_ID' => $deal['ID'], 'ENTITY_TYPE' => 'DEAL', 'ENTITY_FIELD' => 'STAGE_ID']
        );

        if ($events->SelectedRowsCount() != 0) {
            $eventList = [];
            $eventList[] = [
                'time' => $deal['DATE_CREATE'],
            ];

            while ($event = $events->fetch()) {
                $evs[] = $event;
                $eventList[] = [
                    'stage' => [
                        0 => $event['EVENT_TEXT_1'],
                        1 => $event['EVENT_TEXT_2'],
                    ],
                    'time'  => $event['DATE_CREATE'],
                ];
            }

            foreach ($eventList as $index => $history) {
                $time = 0;

                if ($index == 0) {
                    $date1 = new DateTime($history['time']);
                    $date2 = new DateTime($eventList[$index + 1]['time']);
                    $interval = $date1->diff($date2);
                    $time += (int)$interval->format('%Y') * 365 * 1440;
                    $time += (int)$interval->format('%M') * 30 * 1440;
                    $time += (int)$interval->format('%D') * 1440;
                    $time += (int)$interval->format('%H') * 60;
                    $time += (int)$interval->format('%I');
                    if ((int)$interval->format('%S') > 0) {
                        $time++;
                    }
                    $time = excludeHolidays($date1, $date2, $time);
                    if (!empty($rows[$eventList[$index + 1]['stage'][0]])) {
                        $rows[$eventList[$index + 1]['stage'][0]]['data'] += $time;
                    }

                    continue;
                }

                if (empty($eventList[$index + 1])) {
                    $date1 = new DateTime($history['time']);
                    $date2 = new DateTime('now');
                    $interval = $date1->diff($date2);
                    $time += (int)$interval->format('%Y') * 365 * 1440;
                    $time += (int)$interval->format('%M') * 30 * 1440;
                    $time += (int)$interval->format('%D') * 1440;
                    $time += (int)$interval->format('%H') * 60;
                    $time += (int)$interval->format('%I');
                    if ((int)$interval->format('%S') > 0) {
                        $time++;
                    }
                    $time = excludeHolidays($date1, $date2, $time);
                    if (!empty($rows[$history['stage'][1]])) {
                        $rows[$history['stage'][1]]['data'] += $time;
                    }
                } else {
                    $date1 = new DateTime($history['time']);
                    $date2 = new DateTime($eventList[$index + 1]['time']);
                    $interval = $date1->diff($date2);
                    $time += (int)$interval->format('%Y') * 365 * 1440;
                    $time += (int)$interval->format('%M') * 30 * 1440;
                    $time += (int)$interval->format('%D') * 1440;
                    $time += (int)$interval->format('%H') * 60;
                    $time += (int)$interval->format('%I');
                    if ((int)$interval->format('%S') > 0) {
                        $time++;
                    }
                    $time = excludeHolidays($date1, $date2, $time);
                    if (!empty($rows[$history['stage'][1]])) {
                        $rows[$history['stage'][1]]['data'] += $time;
                    }
                }
            }
        }

        if (!empty($_GET['STAGES'])) {
            $stopIteration = true;
            foreach ($_GET['STAGES'] as $index => $stage) {
                $stageExplode = explode(':', $stage);
                $dir = 'Общее';

                if (count($stageExplode) > 1) {
                    $dir = $arResult['VIEW_DIRECTION'][$stageExplode[0]];
                }

                $stageName = $stagesArray[$stage];

                if (!empty($rows[$stageName]['data']) && $rows['DIRECTION']['data'] === $dir) {
                    if ((int)$TIME_FILTER !== 0) {
                        if ((int)$rows[$stageName]['data'] >= (int)$TIME_FILTER) {
                            $stopIteration = false;
                            break;
                        }
                    } else {
                        $stopIteration = false;
                        break;
                    }
                }
            }
        } elseif ($TIME_FILTER !== 0) {
            foreach ($rows as $key => $val) {
                if (is_numeric($val['data'])) {
                    if ($val['data'] < $TIME_FILTER) {
                        $stopIteration = true;
                        break;
                    }
                }
            }
        }

        if ($stopIteration) {
            continue;
        }

        foreach ($timeStages as $stage => $data) {
            if (!empty($rows[$stage]['data'])) {
                $timeStages[$stage]['time'] += $rows[$stage]['data'];
                $timeStages[$stage]['count']++;
            }
        }

        foreach ($rows as $index => $row) {
            if (is_numeric($row['data'])) {
                $minutes = $row['data'];
                $hours = floor($minutes / 60);
                $min = $minutes - ($hours * 60);

                if ($hours >= 24) {
                    $newHours = $hours % 24;
                    $day = ($hours - $newHours) / 24;
                    $rows[$index]['data'] = $day . ' д.' . $newHours . ' ч. ' . $min . ' м.';
                } elseif ($hours <= 0) {
                    $rows[$index]['data'] = $min . ' м.';
                } else {
                    $rows[$index]['data'] = $hours . ' ч. ' . $min . ' м.';
                }
            }
        }

        $arResult['TABLE'][$deal['ID']] = $rows;
    }

    foreach ($timeStages as $stage => $data) {
        if ($data['time'] !== 0) {
            $middle = (int)$data['time'] / $data['count'];
            $hours = floor($middle / 60);
            $min = $middle - ($hours * 60);

            if ($hours > 24) {
                $newHours = $hours % 24;
                $day = ($hours - $newHours) / 24;
                $timeStages[$stage]['middle'] = $day . ' д.' . $newHours . ' ч. ' . floor($min) . ' м.';
            } elseif ($hours <= 0) {
                $timeStages[$stage]['middle'] = floor($min) . ' м.';
            } else {
                $timeStages[$stage]['middle'] = $hours . ' ч. ' . floor($min) . ' м.';
            }
        }
    }

    $arResult['TIME_STAGES'] = $timeStages;
}

$arResult['RUN'] = $run;
if ($excelView) {
    $APPLICATION->RestartBuffer();
    Header("Content-Type: application/force-download");
    Header("Content-Type: application/octet-stream");
    Header("Content-Type: application/download");
    Header("Content-Disposition: attachment;filename=report.xls");
    Header("Content-Transfer-Encoding: binary");
    $this->IncludeComponentTemplate('excel');
    exit;
} else {
    $this->IncludeComponentTemplate();
}