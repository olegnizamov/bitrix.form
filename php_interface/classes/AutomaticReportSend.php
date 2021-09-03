<?php

namespace Onizamov;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime as BxDateTime;
use Bitrix\Main\Mail\Event;

class AutomaticReportSend
{
    /** Количество целый дней, которые прошли с прошлого запуска. Из-за длительности генерации отчета может пройти меньше 7 дней*/
    const PERIOD = 6;

    public static function execute(): void
    {
        Loader::includeModule('crm');
        $arrDealType = [12 => "Отчёт №1: Первая поставка клиенту", 11 => "Отчёт №2: Тендеры"];
        foreach ($arrDealType as $dealTypeItem => $topicName) {
            /** Получаем данные из конфигов*/
            $optionValue = Option::get("onizamov", "automatic_report_type_" . $dealTypeItem);
            if (empty($optionValue)) {
                continue;
            }
            $optionValue = json_decode($optionValue, true);

            /** Получаем список дней*/
            $arrDaysOfWeek = [];
            foreach (json_decode($optionValue['DAY_OF_WEEK'], true) as $key => $item) {
                $parsingItem = json_decode($item, true);
                $arrDaysOfWeek[$parsingItem['VALUE']] = $parsingItem['NAME'];
            }

            $filter = $optionValue['FILTER'];
            $fields = self::prepareFields($optionValue['LIFT_FIELDS']);
            $recipients = str_replace(
                ' ',
                '',
                str_replace(
                    ';',
                    ',',
                    $optionValue['RECIPIENTS']
                )
            );

            if (empty($recipients) || empty($fields) || empty($arrDaysOfWeek)) {
                continue;
            }

            /** Получаем последний запуск программы*/
            $strNextDateTimeSend = Option::get("onizamov", "automatic_report_next_send_type_" . $dealTypeItem);
            if (empty($strNextDateTimeSend)) {
                self::sendEmail(
                    $fields,
                    $dealTypeItem,
                    $filter,
                    $recipients,
                    $topicName
                );
            } else {
                /**Если прошло время больше периода*/
                $origin = new \DateTime($strNextDateTimeSend);
                foreach ($arrDaysOfWeek as $keyOfDay => $dayWeek) {
                    $nextDay = new \DateTime();
                    /** Если сегодня день недели не совпадает*/
                    if (strtolower($nextDay->format('l')) !== strtolower($keyOfDay)) {
                        $nextDay->modify('next ' . strtolower($keyOfDay));
                    }
                    $interval = $origin->diff($nextDay);
                    /** Если между запусками прошло больше целых 6 дней */
                    if ($interval->days >= self::PERIOD) {
                        self::sendEmail(
                            $fields,
                            $dealTypeItem,
                            $filter,
                            $recipients,
                            $topicName
                        );
                    }
                }
            }
        }
    }

    /**
     * Метод отправки сообщения.
     *
     * @param string $recipients
     * @param string $topic
     * @param string $filePath
     */
    public static function sendMessage(string $recipients, string $topic, string $filePath): void
    {
        Event::send(
            [
                "EVENT_NAME" => "AUTOMATIC_REPORT",
                "LID"        => "s1",
                "C_FIELDS"   => [
                    "RECIPIENTS" => $recipients,
                    "TOPIC"      => $topic,
                ],
                "FILE"       => [
                    $filePath,
                ],
            ]
        );
    }

    /**
     * Метод генерации excel файла.
     *
     * @param array $fields
     * @param int $dealTypeItem
     * @param array $params
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function generateExcel(
        array $fields = [],
        int $dealTypeItem = 0,
        array $params = []
    ): string {
        if (empty($fields)) {
            return '';
        }

        $date = new \DateTime();
        $fileName = $dealTypeItem . '_' . $date->format('d-m-Y_H-i-s') . '.xls';
        $filePath = $_SERVER["DOCUMENT_ROOT"] . '/upload/excel/' . $fileName;

        $fieldsXmlId = array_column($fields, 'VALUE');
        $fieldsName = array_column($fields, 'NAME');
        $fieldsTypeByID = array_column($fields, 'TYPE', 'VALUE');

        $rows = [$fieldsName];
        $ormDeal = \Bitrix\Crm\DealTable::query()
            ->setSelect(
                $fieldsXmlId
            )
            ->where('CATEGORY_ID', $dealTypeItem);

        /** Устанавливаем фильтр ORM сущности */
        self::setFilter($params, $ormDeal);

        $dealCollection = $ormDeal->fetchCollection();
        foreach ($dealCollection as $dealObj) {
            $rows[] = self::getRowData($fieldsXmlId, $fieldsTypeByID, $dealObj);
        }
        self::saveFile($filePath, $rows);
        return $filePath;
    }


    /**
     * Метод подготовки полей.
     *
     * @param string $fields
     * @return array
     */
    public static function prepareFields(string $fields): array
    {
        $result = [];
        foreach (json_decode($fields, true) as $key => $item) {
            $result[$key] = json_decode($item, true);
        }

        return $result;
    }

    /**
     * Общий метод отправки email. Описывает логику выполнения отправки.
     *
     * @param array $fields
     * @param int $dealTypeItem
     * @param $dateCreateFrom
     * @param $dateCreateTo
     * @param $recipients
     * @param string $topicName
     * @return BxDateTime
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private static function sendEmail(
        array $fields,
        int $dealTypeItem,
        array $params,
        $recipients,
        string $topicName
    ): void {
        /** Генерируем excel файл */
        $filePath = self::generateExcel($fields, $dealTypeItem, $params);
        /** Отправляем сообщение */
        //self::sendMessage($recipients, $topicName, $filePath);
        /** Устанавливаем следующий запуск рассылки*/
        $nextDateTimeSend = new BxDateTime();
        $nextDateTimeSend->add('+7 days');
        Option::set("onizamov", "automatic_report_next_send_type_" . $dealTypeItem, $nextDateTimeSend);
    }

    /**
     * @param array $params
     * @param \Bitrix\Main\ORM\Query\Query $ormDeal
     * @throws \Bitrix\Main\ObjectException
     */
    private static function setFilter(array $params, \Bitrix\Main\ORM\Query\Query $ormDeal): void
    {
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                if (empty($value)) {
                    continue;
                }
                switch ($key) {
                    case 'DATE_CREATE' . '_from':
                    case 'UF_CRM_1625580273' . '_from':
                    case 'UF_CRM_1625751368' . '_from':
                        $key = str_replace('_from', '', $key);
                        if (!empty($params[$key . '_datesel'])) {
                            [$from, $to] = DateCalculator::calculate($params[$key . '_datesel']);
                            if (empty($from) && empty($to)) {
                                $ormDeal->where(
                                    $key,
                                    ">=",
                                    new \Bitrix\Main\Type\DateTime($value)
                                );
                            }else{
                                $ormDeal->where(
                                    $key,
                                    ">=",
                                    new \Bitrix\Main\Type\DateTime($from)
                                );
                                $ormDeal->where(
                                    $key,
                                    "<=",
                                    new \Bitrix\Main\Type\DateTime($to)
                                );
                            }
                        } else {
                            $ormDeal->where(
                                $key,
                                ">=",
                                new \Bitrix\Main\Type\DateTime($value)
                            );
                        }

                        break;
                    case 'DATE_CREATE' . '_to':
                    case 'UF_CRM_1625580273' . '_to':
                    case 'UF_CRM_1625751368' . '_to':
                        $key = str_replace('_to', '', $key);
                        if (empty($params[$key . '_datesel'])) {
                            $ormDeal->where(
                                $key,
                                "<=",
                                new \Bitrix\Main\Type\DateTime($value)
                            );
                        }
                        break;

                    case 'STAGE_ID':
                        $ormDeal->whereIn(
                            'STAGE_ID',
                            $value
                        );
                        break;
                }
            }
        }
    }

    /**
     * @param array $fieldsXmlId
     * @param array $fieldsTypeByID
     * @param $dealObj
     * @param array $row
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private static function getRowData(array $fieldsXmlId, array $fieldsTypeByID, $dealObj): array
    {
        $row = [];
        foreach ($fieldsXmlId as $propXmlId) {
            switch ($fieldsTypeByID[$propXmlId]) {
                case "string":
                case "integer":
                case "double":
                case "crm_currency":
                    $row[] = $dealObj->get($propXmlId);
                    break;
                case "char":
                case "boolean":
                    $row[] = !$dealObj->get($propXmlId) ? 'нет' : 'да';
                    break;
                case "date":
                    if (!empty($dealObj->get($propXmlId))) {
                        [$date, $time] = explode(' ', $dealObj->get($propXmlId)->toString());
                        $row[] = $date;
                    } else {
                        $row[] = '';
                    }
                    break;
                case "crm_status":
                case "STAGE_ID":
                    if (!empty($dealObj->get($propXmlId))) {
                        $statusObj = \Bitrix\Crm\StatusTable::query()
                            ->setSelect(
                                ['NAME']
                            )
                            ->where('STATUS_ID', $dealObj->get($propXmlId))
                            ->whereIn('ENTITY_ID', ['DEAL_TYPE', 'DEAL_STAGE_12', 'DEAL_STAGE_11'])
                            ->fetchObject();

                        if ($statusObj !== null) {
                            $row[] = $statusObj->get('NAME');
                        } else {
                            $row[] = '';
                        }
                    } else {
                        $row[] = '';
                    }
                    break;
                case "crm_category":
                    if (!empty($dealObj->get($propXmlId))) {
                        $dealCategoryObj = \Bitrix\Crm\Category\Entity\DealCategoryTable::query()
                            ->setSelect(
                                ['NAME']
                            )
                            ->where('ID', $dealObj->get($propXmlId))
                            ->fetchObject();
                        if ($dealCategoryObj !== null) {
                            $row[] = $dealCategoryObj->get('NAME');
                        } else {
                            $row[] = '';
                        }
                    } else {
                        $row[] = '';
                    }
                    break;
                case "crm_company":
                    if (!empty($dealObj->get($propXmlId))) {
                        $companyObj = \Bitrix\Crm\CompanyTable::getById($dealObj->get($propXmlId))->fetchObject();
                        if ($companyObj !== null) {
                            $row[] = $companyObj->getTitle();
                        } else {
                            $row[] = '';
                        }
                    } else {
                        $row[] = '';
                    }
                    break;
                case "employee":
                case "user":
                    if (!empty($dealObj->get($propXmlId))) {
                        $userObj = \Bitrix\Main\UserTable::getById($dealObj->get($propXmlId))->fetchObject();
                        if ($userObj !== null) {
                            $row[] = $userObj->getLastName() . ' ' . $userObj->getName();
                        } else {
                            $row[] = '';
                        }
                    } else {
                        $row[] = '';
                    }
                    break;
                case "datetime":
                    if (!empty($dealObj->get($propXmlId))) {
                        $row[] = $dealObj->get($propXmlId)->toString();
                    } else {
                        $row[] = '';
                    }
                    break;
                case "enumeration":
                    if (!empty($dealObj->get($propXmlId))) {
                        $info = $dealObj->get($propXmlId);
                        if (is_array($info)) {
                            $info = current($info);
                        }
                        $enumObject = \Onizamov\FieldEnumTable::getById($info)->fetchObject();
                        if ($enumObject !== null) {
                            $row[] = $enumObject->getValue();
                        } else {
                            $row[] = '';
                        }
                    } else {
                        $row[] = '';
                    }
                    break;
                case "iblock_element":
                    if (!empty($dealObj->get($propXmlId))) {
                        $arrElementTitle = [];
                        if (is_array($dealObj->get($propXmlId))) {
                            foreach ($dealObj->get($propXmlId) as $elementId) {
                                $res = \CIBlockElement::GetByID($elementId);
                                if ($ar_res = $res->GetNext()) {
                                    $arrElementTitle[] = $ar_res['NAME'];
                                }
                            }
                            $row[] = implode(',', $arrElementTitle);
                        } else {
                            $res = \CIBlockElement::GetByID($dealObj->get($propXmlId));
                            if ($ar_res = $res->GetNext()) {
                                $arrElementTitle[] = $ar_res['NAME'];
                            }
                        }
                        $row[] = implode(',', $arrElementTitle);
                    } else {
                        $row[] = '';
                    }
                    break;
                case "money":
                    if (!empty($dealObj->get($propXmlId))) {
                        $row[] = $dealObj->get($propXmlId);
                    } else {
                        $row[] = '';
                    }
                    break;
                case "file":
                    if (!empty($dealObj->get($propXmlId))) {
                        $row[] = 'https://crm.akkermann.ru' . \CFile::GetPath($dealObj->get($propXmlId));
                    } else {
                        $row[] = '';
                    }
                    break;
                case "iblock_section":
                    $row[] = '';
                    break;
                case "crm":
                case "crm_contact":
                    if (!empty($dealObj->get($propXmlId))) {
                        $contactObj = \Bitrix\Crm\ContactTable::getById($dealObj->get($propXmlId))->fetchObject();
                        if ($contactObj !== null) {
                            $row[] = $contactObj->getFullName();
                        } else {
                            $row[] = '';
                        }
                    } else {
                        $row[] = '';
                    }
                    break;
                default:
                    $row[] = $dealObj->get($propXmlId);
            }
        }
        return $row;
    }

    /**
     * @param string $filePath
     * @param array $rows
     */
    private static function saveFile(string $filePath, array $rows): void
    {
        $fp = fopen($filePath, 'w');
        //file_put_contents($filePath, chr(239) . chr(187) . chr(191));
        fwrite($fp, '<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />');
        fwrite($fp, '<table border="1"><thead></thead><tbody>');
        foreach ($rows as $row) {
            // fputcsv($fp, $row);
            fwrite($fp, '<tr>');
            foreach ($row as $column) {
                fwrite($fp, '<td>');
                fwrite($fp, $column);
                fwrite($fp, '</td>');
            }
            fwrite($fp, '</tr>');
        }

        fwrite($fp, '</tbody></table>');
        fclose($fp);
    }
}