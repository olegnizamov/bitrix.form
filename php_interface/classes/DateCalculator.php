<?php

namespace Onizamov;

use Bitrix\Main\UI\Filter\DateType;

class DateCalculator
{
    const FORMAT = 'd.m.Y H:i:s';

    /**
     * @param $filterType
     * @return array
     * @throws \Exception
     */
    public static function calculate(string $filterType): array
    {
        $resultFromDate = null;
        $resultToDate = null;
        switch ($filterType) {
            case DateType::CURRENT_WEEK:
                $dateTimeFrom = self::getCurrentUserDate();
                if (strtolower($dateTimeFrom->format('l')) !== self::getFirstWeekDayWord()) {
                    $dateTimeFrom->modify('last ' . self::getFirstWeekDayWord());
                } else {
                    $dateTimeFrom->modify(self::getFirstWeekDayWord() . ' this week');
                }
                $dateTimeFrom->modify('midnight');

                $resultFromDate = $dateTimeFrom;

                $dateTimeTo = clone $dateTimeFrom;
                $dateTimeTo->add(new \DateInterval('P7D'));
                $dateTimeTo->sub(new \DateInterval('PT1S'));
                $resultToDate = $dateTimeTo;
                break;


            case DateType::CURRENT_MONTH:
                $dateTimeFrom = self::getCurrentUserDate();
                $dateTimeFrom->modify('first day of this month');
                $dateTimeFrom->modify('midnight');

                $resultFromDate = $dateTimeFrom;

                $dateTimeTo = clone $dateTimeFrom;
                $dateTimeTo->modify('last day of this month');
                $dateTimeTo->setTime(23, 59, 59);
                $resultToDate = $dateTimeTo;
                break;
            case DateType::NEXT_MONTH:
                $dateTimeFrom = self::getCurrentUserDate();
                $dateTimeFrom->modify('first day of next month');
                $dateTimeFrom->modify('midnight');

                $resultFromDate = $dateTimeFrom;

                $dateTimeTo = self::getCurrentUserDate();
                $dateTimeTo->modify('last day of next month');
                $dateTimeTo->setTime(23, 59, 59);
                $resultToDate = $dateTimeTo;
                break;
            case DateType::CURRENT_QUARTER:
                [$from, $to] = self::getQuarterRange(self::getCurrentUserDate());
                $resultFromDate = $from;
                $resultToDate = $to;
                break;
            case DateType::CURRENT_DAY:
                $resultToDate = self::getCurrentUserDate();
                $resultToDate->setTime(00, 00, 00);
                $resultFromDate = self::getCurrentUserDate();
                break;
            case DateType::TOMORROW:
                $resultFromDate = self::getCurrentUserDate();
                $resultFromDate->modify('+1 day');
                $resultFromDate->setTime(00, 00, 00);
                $resultToDate = self::getCurrentUserDate();
                $resultToDate->modify('+1 day');
                break;
            case DateType::LAST_7_DAYS:
                [$resultFromDate, $resultToDate] = self::buildLastDaysDates(7);
                break;
            case DateType::YESTERDAY:
                [$resultFromDate, $resultToDate] = self::buildLastDaysDates(1);
                break;
            case DateType::LAST_30_DAYS:
                [$resultFromDate, $resultToDate] = self::buildLastDaysDates(30);
                break;
            case DateType::LAST_60_DAYS:
                [$resultFromDate, $resultToDate] = self::buildLastDaysDates(60);
                break;
            case DateType::LAST_90_DAYS:
                [$resultFromDate, $resultToDate] = self::buildLastDaysDates(90);
                break;
            case DateType::LAST_WEEK:
                $dateTimeFrom = self::getCurrentUserDate();
                $dateTimeFrom->modify(self::getFirstWeekDayWord() . ' previous week');
                $dateTimeFrom->modify('midnight');

                $resultFromDate = $dateTimeFrom;

                $dateTimeTo = clone $dateTimeFrom;
                $dateTimeTo->add(new \DateInterval('P7D'));
                $dateTimeTo->sub(new \DateInterval('PT1S'));
                $resultToDate = $dateTimeTo;
                break;
            case DateType::LAST_MONTH:
                $dateTimeFrom = self::getCurrentUserDate();
                $dateTimeFrom->modify('first day of previous month');
                $dateTimeFrom->modify('midnight');

                $resultFromDate = $dateTimeFrom;

                $dateTimeTo = clone $dateTimeFrom;
                $dateTimeTo->add(new \DateInterval('P1M'));
                $dateTimeTo->sub(new \DateInterval('PT1S'));
                $resultToDate = $dateTimeTo;
                break;
        }
        if ($resultToDate) {
            $resultToDate->setTime(23, 59, 59);
        }
        return [$resultFromDate->format(self::FORMAT), $resultToDate->format(self::FORMAT)];
    }

    private static function getFirstWeekDayWord()
    {
        return 'monday';
    }

    private static function getCurrentUserDate()
    {

        return new  \DateTime();
    }

    /**
     */
    private static function getQuarterRange($date = null, $quarter = null)
    {
        if ($quarter === null) {
            $quarter = intval(((int)$date->format('n') + 2) / 3);
        }

        $ranges = [
            1 => ['01.01', '31.03'],
            2 => ['01.04', '30.06'],
            3 => ['01.07', '30.09'],
            4 => ['01.10', '31.12'],
        ];

        return [
            self::getNormalDate($ranges[$quarter][0] . $date->format('Y'), 'd.m.Y'),
            self::getNormalDate($ranges[$quarter][1] . $date->format('Y'), 'd.m.Y'),
        ];
    }

    /**
     * Return normalize object date
     */
    private static function getNormalDate($datetime, $format = null)
    {
        if (!($datetime instanceof \DateTime)) {
            if (!($datetime instanceof \Bitrix\Main\Type\Date)) {
                $datetime = new \Bitrix\Main\Type\DateTime($datetime, $format);
            }
            $datetime = \DateTime::createFromFormat('d m Y H i s P', $datetime->format('d m Y H i s P'));
        }
        return clone $datetime;
    }

    private static function buildLastDaysDates($days)
    {
        $dateTime = self::getCurrentUserDate();
        $dateTimeFrom = clone $dateTime;
        $dateTimeFrom->sub(new \DateInterval('P' . $days . 'D'));
        $dateTimeFrom->setTime(0, 0, 0);
        $dateTimeTo = clone $dateTimeFrom;
        $dateTimeTo->add(new \DateInterval('P' . $days . 'D'));
        $dateTimeTo->sub(new \DateInterval('PT1S'));
        $dateTimeTo->setTime(23, 59, 59);

        return [$dateTimeFrom, $dateTimeTo];
    }

}