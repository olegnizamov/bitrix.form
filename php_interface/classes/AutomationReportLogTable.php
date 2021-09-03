<?php

namespace ONizamov;

use Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\Type\DateTime;


/**
 * Class AutomationReportLogTable
 **/
class AutomationReportLogTable extends DataManager
{
    /**
     * Возвращает название таблицы.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'automation_report_log';
    }

    /**
     * Возвращает карту сущности.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            new IntegerField(
                'ID',
                [
                    'primary'      => true,
                    'autocomplete' => true,
                    'title'        => 'ID',
                ]
            ),
            new IntegerField(
                'DEAL_TYPE',
                [
                    'required' => true,
                    'title'    => 'DEAL_TYPE',
                ]
            ),
            new DatetimeField('SEND_TIME', ['default_value' => new DateTime()]),
        ];
    }
}