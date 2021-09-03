<?php

use Bitrix\Main\UserTable;
use Bitrix\Main\Loader;

class TimesheetsAvailableComponent extends CBitrixComponent
{
    public $arFields = [];
    public $users = [];

    public function executeComponent()
    {
        Loader::includeModule('crm');
        Loader::includeModule('bizproc');

        $this->getUsers();
        $this->saveData();
        $this->includeComponentTemplate();
    }

    private function getUsers()
    {
        $users = UserTable::GetList(['filter' => ['ACTIVE' => 'Y'], 'order' => ['LAST_NAME' => 'ASC']]);
        if ($users) {
            while ($user = $users->Fetch()) {
                $this->arResult['USERS'][$user['ID']] = sprintf("%s %s %s", $user['LAST_NAME'], $user['NAME'], $user['SECOND_NAME']);
            }
        }
    }

    private function saveData()
    {
        global $USER;

        $save = false;

        $this->arResult['CAN_CHANGE_FROM'] = in_array($this->arParams['ABSENT_MANAGEMENT_GROUP'], $USER->GetUserGroupArray());

        if ($_POST) {
            foreach ($_POST as $index => $value) {
                $this->arResult['FIELDS'][$index] = $value;
            }

            $save = true;
        }

        if (!$this->arResult['FIELDS']['FROM'] || !$this->arResult['CAN_CHANGE_FROM']) {
            $this->arResult['FIELDS']['FROM'] = $USER->GetID();
        }

        if ($save) {
            if ($this->arResult['FIELDS']['PERIOD_FROM'] > $this->arResult['FIELDS']['PERIOD_TO']) {
                $this->arResult['ERROR'] = 'Не корректно указан период';
                return false;
            }

            if (!isset($this->arResult['USERS'][$this->arResult['FIELDS']['FROM']])) {
                $this->arResult['ERROR'] = 'Не корректное значение поля "От Кого"';
                return false;
            }

            if (!isset($this->arResult['USERS'][$this->arResult['FIELDS']['DEPUT']])) {
                $this->arResult['ERROR'] = 'Не корректное значение поля "Кому"';
                return false;
            }

            $el = new \CIBlockElement;

            $data = [
                'IBLOCK_SECTION_ID' => false,
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'PROPERTY_VALUES' => $this->arResult['FIELDS'],
                'NAME' => '-',
                'ACTIVE' => 'Y',
            ];

            if (!$this->arResult['ID'] = $el->Add($data)) {
                $this->arResult['ERROR'] = $el->LAST_ERROR;
                return false;
            }

            $templates = \CBPWorkflowTemplateLoader::GetList(
                array('SORT' => 'ASC', 'NAME' => 'ASC'),
                array(
                    "DOCUMENT_TYPE" => ['lists', 'Bitrix\Lists\BizprocDocumentLists', 'iblock_' . $this->arParams['IBLOCK_ID']],
                    "ACTIVE" => "Y",
                    "IS_SYSTEM" => "N",
                    'AUTO_EXECUTE' => \CBPDocumentEventType::Create
                ),
                false,
                false,
                ["ID", "NAME"]
            );

            while ($template = $templates->Fetch()) {
                $runtime = \CBPRuntime::GetRuntime();
                $wi = $runtime->CreateWorkflow($template['ID'], ['lists', 'Bitrix\Lists\BizprocDocumentLists', $this->arResult['ID']], []);
                $wi->Start();
            }


            if ($this->saveAbsence($this->arResult['FIELDS'])) {
                $this->updateCompany($this->arResult['FIELDS']);
            }
        }
    }

    private function saveAbsence(array $data)
    {
        $el = new \CIBlockElement;

        switch ($data['TYPE']) {
            case "211":
                $absence_type = 1;
                break;
            case "212":
                $absence_type = 2;
                break;
            case "213":
                $absence_type = 3;
                break;
            default:
                $absence_type = 7;
        }

        $absence_type =
            $absenceData = [
                'IBLOCK_ID' => $this->arParams['ABSENCE_IBLOCK_ID'],
                'PROPERTY_VALUES' => [
                    'USER' => $data['FROM'],
                    'ABSENCE_TYPE' => $absence_type,
                ],
                'ACTIVE_FROM' => $data['PERIOD_FROM'],
                'ACTIVE_TO' => $data['PERIOD_TO'],
                'NAME' => $data['COMMENT'],
                'ACTIVE' => 'Y',
            ];

        if (!$el->Add($absenceData)) {
            $this->arResult['ERROR'] = $el->LAST_ERROR;
            return false;
        }

        return true;
    }

    private function updateCompany(array $user)
    {

        $companies = \Bitrix\Crm\CompanyTable::getList([
            'select' => ['ID', 'UF_CRM_1602652826'],
            'filter'=>[
            'LOGIC' => 'OR',
            'ASSIGNED_BY_ID' => $user['FROM'],
            'UF_CRM_1591266533' => $user['FROM'],
            'UF_CRM_1586753048' => $user['FROM'],
            'UF_CRM_1586753089' => $user['FROM'],
        ]]);

        $CCrmCompany = new CCrmCompany;
        while ($company = $companies->fetch()) {
            $company['UF_CRM_1602652826'][] = $user['DEPUT'];
            $company['UF_CRM_1602652826'] = array_unique($company['UF_CRM_1602652826']);

            if (!$CCrmCompany->Update($company['ID'], $company)) {
                $this->arResult['ERROR'] = $CCrmCompany->LAST_ERROR;
                return false;
            }
        }
    }
}
