<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!CModule::IncludeModule('crm')) {
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
$FILTER['CLOSED'] = 'Y';

if (!empty($_GET['DATE_CREATE_FROM'])) {
    $FILTER['>=DATE_CREATE'] = $_GET['DATE_CREATE_FROM'];
    $run = true;
}
if (!empty($_GET['DATE_CREATE_TO'])) {
    $FILTER['<=DATE_CREATE'] = $_GET['DATE_CREATE_TO'];
    $run = true;
}
if (!empty($_GET['BEGINDATE_FROM'])) {
    $FILTER['>=BEGINDATE'] = $_GET['BEGINDATE_FROM'];
    $run = true;
}
if (!empty($_GET['BEGINDATE_TO'])) {
    $FILTER['<=BEGINDATE'] = $_GET['BEGINDATE_TO'];
    $run = true;
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

$arResult['DIRECTION']['common'] = 'Общее';
$arResult['TIME_DIRECTION']['Общее'] = ['time' => 0, 'count' => 0, 'middle' => ''];

while ($cat = $category->fetch()) {
    $arResult['DIRECTION']['C' . (string)$cat['ID']] = $cat['NAME'];
    $arResult['TIME_DIRECTION'][$cat['NAME']] = ['time' => 0, 'count' => 0, 'middle' => ''];
}

$arResult['HEAD'] = [
    'TITLE'       => GetMessage('TITLE'),
    'DATE_CREATE' => GetMessage('DATE_CREATE'),
    'BEGINDATE'   => GetMessage('BEGINDATE'),
    'DIRECTION'   => GetMessage('DIRECTION'),
    'TIME'        => GetMessage('TIME'),
];

foreach ($stages as $index => $stage) {
    $reversStage[$stage['NAME']] = $stage['STATUS_ID'];
}

if ($run) {
    while ($deal = $deals->fetch()) {
        $dealTime = 0;
        $stopIteration = false;
        $eventList = [];

        $events = CCrmEvent::GetListEx(
            [],
            ['ENTITY_ID' => $deal['ID'], 'ENTITY_TYPE' => 'DEAL', 'ENTITY_FIELD' => 'STAGE_ID']
        );

        while ($event = $events->fetch()) {
            $eventList[] = $event;
        }

        $date1 = new DateTime($deal['DATE_CREATE']);
        $date2 = new DateTime($eventList[count($eventList) - 1]['DATE_CREATE']);
        $interval = $date1->diff($date2);
        $dealTime += (int)$interval->format('%Y') * 365 * 1440;
        $dealTime += (int)$interval->format('%M') * 30 * 1440;
        $dealTime += (int)$interval->format('%D') * 1440;
        $dealTime += (int)$interval->format('%H') * 60;
        $dealTime += (int)$interval->format('%I');

        $dealTime = excludeHolidays($date1, $date2, $dealTime);
        $minutes = $dealTime;
        $hours = floor($minutes / 60);
        $min = $minutes - ($hours * 60);

        if ($dealTime < $TIME_FILTER) {
            $stopIteration = true;
        }

        if ($stopIteration) {
            continue;
        }

        if ($hours >= 24) {
            $newHours = $hours % 24;
            $day = ($hours - $newHours) / 24;
            $dealTime = $day . ' д.' . $newHours . ' ч. ' . $min . ' м.';
        } elseif ($hours <= 0) {
            $dealTime = $min . ' м.';
        } else {
            $dealTime = $hours . ' ч. ' . $min . ' м.';
        }

        $stageExplode = explode(':', $deal['STAGE_ID']);
        $direction = empty($arResult['DIRECTION'][$stageExplode[0]]) ? 'Общее' : $arResult['DIRECTION'][$stageExplode[0]];

        if (!empty($_GET['STAGES'])) {
            $stopIteration = true;
            foreach ($_GET['STAGES'] as $index => $stage) {
                if ($arResult['DIRECTION'][$stage] === $direction) {
                    $stopIteration = false;
                    break;
                }
            }
        }

        if ($stopIteration) {
            continue;
        }

        $arResult['DEALS'][$deal['ID']] = [
            'TITLE'       => $deal['TITLE'],
            'DATE_CREATE' => $deal['DATE_CREATE'],
            'BEGINDATE'   => $deal['BEGINDATE'],
            'DIRECTION'   => $direction,
            'TIME'        => $dealTime,
        ];

        $arResult['TIME_DIRECTION'][$direction]['time'] += $minutes;
        $arResult['TIME_DIRECTION'][$direction]['count']++;
    }

    foreach ($arResult['TIME_DIRECTION'] as $direction => $data) {
        if ($data['time'] == 0) {
            continue;
        }
        $minutes = $data['time'] / $data['count'];
        $hours = floor($minutes / 60);
        $min = $minutes - ($hours * 60);

        if ($hours > 24) {
            $newHours = $hours % 24;
            $day = ($hours - $newHours) / 24;
            $arResult['TIME_DIRECTION'][$direction]['middle'] = $day . ' д.' . $newHours . ' ч. ' . (int)$min . ' м.';
        } elseif ($hours <= 0) {
            $arResult['TIME_DIRECTION'][$direction]['middle'] = (int)$min . ' м.';
        } else {
            $arResult['TIME_DIRECTION'][$direction]['middle'] = $hours . ' ч. ' . (int)$min . ' м.';
        }
    }
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