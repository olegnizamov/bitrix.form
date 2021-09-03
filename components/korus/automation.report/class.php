<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\UserFieldLangTable;
use Bitrix\Main\UserFieldTable;

class AutomationReportComponent extends CBitrixComponent
{
    public $arFields = [];
    public $arFilter = [];

    /** Тайтлы стандартных свойств */
    public $fieldsTitle = [
        'CATEGORY_ID'          => 'Направление',
        'IS_RETURN_CUSTOMER'   => 'Повторная сделка',
        'STAGE_ID'             => 'Стадия сделки',
        'PROBABILITY'          => 'Вероятность',
        'TYPE_ID'              => 'Тип',
        'ORIGINATOR_ID'        => 'Привязка',
        'ASSIGNED_BY_ID'       => 'Ответственный',
        'TITLE'                => 'Название сделки',
        'SOURCE_ID'            => 'Источник',
        'SOURCE_DESCRIPTION'   => 'Дополнительно об источнике',
        'IS_REPEATED_APPROACH' => 'Повторное обращение',
        'OPPORTUNITY'          => 'Сумма',
        'CURRENCY_ID'          => 'Валюта',
        'COMPANY_ID'           => 'Компания',
        'CONTACT_ID'           => 'Источник',
        'COMMENTS'             => 'Комментарий',
        'CLOSEDATE'            => 'Предполагаемая дата закрытия',
        'BEGINDATE'            => 'Дата начала',
        'MODIFY_BY_ID'         => 'Кем изменена',
        'CLOSED'               => 'Сделка закрыта',
        'DATE_CREATE'          => 'Дата создания',
        'CREATED_BY_ID'        => 'Кем создана',
        'UTM_SOURCE'           => 'UTM Source',
        'UTM_MEDIUM'           => 'UTM Medium',
        'UTM_CAMPAIGN'         => 'UTM Campaign',
        'UTM_CONTENT'          => 'UTM Content',
        'UTM_TERM'             => 'UTM Term',
    ];

    /** Fix - Поля, которые не корректно обрабатываются ORM */
    public $unUsedFields = [
        'TAX_VALUE',
        'TAX_VALUE_ACCOUNT',
        'CONTACT_IDS',
        'QUOTE_ID',
        'OPENED',
        'ADDITIONAL_INFO',
        'STAGE_SEMANTIC_ID',
        'IS_NEW',
        'IS_RECURRING',
        'IS_MANUAL_OPPORTUNITY',
        'EXCH_RATE',
        'ACCOUNT_CURRENCY_ID',
        'OPPORTUNITY_ACCOUNT',
        'LEAD_ID',
        'LOCATION_ID',
        'ORIGIN_ID',
    ];

    public function executeComponent()
    {
        Loader::includeModule('crm');
        $dealType = $this->arParams['DEAL_TYPE'] ?: 12;

        /** Устанавливаем ID фильтра */
        $this->arResult['FILTER_ID'] = 'filter_id_' . $dealType;
        /** И поля для поиска */
        $this->arResult['FILTER_FIELDS'] = $this->arParams['ADD_PROP'];
        /**Подключаем данные по стадиям*/
        foreach ($this->arResult['FILTER_FIELDS'] as $index => $field) {
            if ($field['id'] === 'STAGE_ID') {
                $this->arResult['FILTER_FIELDS'][$index]['items'] = $this->getArrStages($dealType);
            }
        }
        /**Удаляем из фильтра ненужные данные*/
        $filterOption = new Bitrix\Main\UI\Filter\Options($this->arResult['FILTER_ID']);
        $filterData = $filterOption->getFilter([]);
        if (!empty($filterData['PRESET_ID'])) {
            unset($filterData['PRESET_ID']);
        }
        if (!empty($filterData['FILTER_ID'])) {
            unset($filterData['FILTER_ID']);
        }
        if (!empty($filterData['FILTER_APPLIED'])) {
            unset($filterData['FILTER_APPLIED']);
        }
        if (!empty($filterData['FIND'])) {
            unset($filterData['FIND']);
        }

        $request = Application::getInstance()->getContext()->getRequest()->toArray();
        if (!empty($request) && !empty($request['RECIPIENTS'])) {
            if (empty($request['LIFT_FIELDS'])) {
                $request['LIFT_FIELDS'] = $_SESSION['AUTO_REPORT_' . $dealType];
            }
            if (empty($request['DAY_OF_WEEK'])) {
                $request['DAY_OF_WEEK'] = $_SESSION['AUTO_REPORT_DAY_OF_WEEK_' . $dealType];
            }

            $request['FILTER'] = $filterData?: $_SESSION['AUTO_REPORT_FILTER_' . $dealType];;
            /** Если данные изменяются */
            Option::delete("onizamov", ["name" => "automatic_report_next_send_type_" . $dealType]);
            Option::set("onizamov", "automatic_report_type_" . $dealType, json_encode($request));
        } else {
            /** Инициализируем данные из базы данных*/
            $optionValue = Option::get("onizamov", "automatic_report_type_" . $dealType);
            $optionValue = json_decode($optionValue, true);
        }

        $this->arResult['VALUES'] = $optionValue ?: $request;
        /** Получаем поля сделки */
        $arrFields = $this->getDealFields();
        $this->arResult['FIELDS'] = json_encode($arrFields);

        /** Получаем дни недели */
        $arrDays = $this->getRusDayWeeks();
        $this->arResult['DAY_OF_WEEK'] = json_encode($arrDays);


        /**Записываем в сессию выбранные поля*/
        if (!empty($this->arResult['VALUES']['LIFT_FIELDS'])) {
            $_SESSION['AUTO_REPORT_' . $dealType] = $this->arResult['VALUES']['LIFT_FIELDS'];
        } else {
            $this->arResult['VALUES']['LIFT_FIELDS'] = $_SESSION['AUTO_REPORT_' . $dealType];
        }

        /**Записываем в сессию выбранные поля*/
        if (!$this->arResult['VALUES']['DAY_OF_WEEK']) {
            $_SESSION['AUTO_REPORT_DAY_OF_WEEK_' . $dealType] = $this->arResult['VALUES']['DAY_OF_WEEK'];
        } else {
            $this->arResult['VALUES']['DAY_OF_WEEK'] = $_SESSION['AUTO_REPORT_DAY_OF_WEEK_' . $dealType];
        }

        $fieldsFromForm = json_decode($this->arResult['VALUES']['LIFT_FIELDS']);
        foreach ($fieldsFromForm as $itemField) {
            $item = json_decode($itemField, true);
            $this->arResult['DEAL_FIELDS'][$item['VALUE']] = $item['NAME'];
        }

        $daysFromForm = json_decode($this->arResult['VALUES']['DAY_OF_WEEK']);
        foreach ($daysFromForm as $day) {
            $item = json_decode($day, true);
            $this->arResult['DAY_OF_WEEK_RESULT'][$item['VALUE']] = $item['NAME'];
        }

        Application::getInstance()->getContext()->getRequest()->clear();
        $this->includeComponentTemplate();
    }

    /**
     * Получить свойства Сделок
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getDealFields(): array
    {
        $fieldsInfo = \CCrmDeal::GetFieldsInfo();
        $userType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], \CCrmDeal::GetUserFieldEntityID());
        $userType->PrepareFieldsInfo($fieldsInfo);
        foreach ($fieldsInfo as $key => $fields) {
            if (in_array($key, $this->unUsedFields)) {
                continue;
            }
            $fieldsForAdding = [];
            $fieldsForAdding['VALUE'] = $key;
            $fieldsForAdding['NAME'] = $fields['LABELS']['LIST'] ?: $this->fieldsTitle[$key] ?: $key;
            $fieldsForAdding['TYPE'] = $fields['TYPE'];
            $arrFields[] = $fieldsForAdding;
        }

        return $arrFields;
    }


    /**
     * @return array[]
     */
    private function getArrStages(int $dealType): array
    {
        $result = [];

        $list = \Bitrix\Crm\StatusTable::getList(
            [
                'filter' => [
                    '=ENTITY_ID' => 'DEAL_STAGE_' . $dealType,
                ],
                'order'  => [
                    'SORT' => 'ASC',
                ],
            ]
        );
        while ($status = $list->fetch()) {
            $result[$status['STATUS_ID']] = $status['NAME'];
        }

        return $result;
    }


    /**
     * @return array[]
     */
    private function getRusDayWeeks(): array
    {
        return [
            ['VALUE' => 'Sunday', 'NAME' => 'Воскресенье'],
            ['VALUE' => 'Monday', 'NAME' => 'Понедельник'],
            ['VALUE' => 'Tuesday', 'NAME' => 'Вторник'],
            ['VALUE' => 'Wednesday', 'NAME' => 'Среда'],
            ['VALUE' => 'Thursday', 'NAME' => 'Четверг'],
            ['VALUE' => 'Friday', 'NAME' => 'Пятница'],
            ['VALUE' => 'Saturday', 'NAME' => 'Суббота'],
        ];
    }

}
