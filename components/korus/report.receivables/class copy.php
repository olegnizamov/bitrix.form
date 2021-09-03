<?php

use Bitrix\Main\UserTable;

use \Bitrix\Main\UI\Filter\Options;
use \Bitrix\Main\UI\PageNavigation;
use \Bitrix\Main\Context;
use Bitrix\Main\Loader;

class ReceivablesComponent extends CBitrixComponent
{
    const GRID_ID = 'report_receivables';
    const IBLOCK_ID = [31, 34];
    const REGION_IBLOCK_ID = 32;

    private $companies = [];
    private $contracts = [];
    private $regions = [];
    private $deptStatuses = [];

    public $arFields = [];
    public $arFilter = [];

    public function executeComponent()
    {
        Loader::includeModule("iblock");

        $this->addComments();
        $this->getHeaders();
        $this->getData();
        $this->includeComponentTemplate();
    }

    private function addComments()
    {

        $context = Context::getCurrent();
        $request = $context->getRequest();
        $fields = $request->get('FIELDS');
        if ($fields) {
            foreach ($fields as $id => $data) {
                $CCrmCompany = new \CCrmCompany;
                if (!$CCrmCompany->Update($id, $data)) {
                    throw new Exception($CCrmCompany->LAST_ERROR);
                }
            }
        }
    }

    private function getContractProps()
    {
        return [
            "PROPERTY_CREDIT_LIMIT",
            "PROPERTY_CREDIT_TERM",
            "PROPERTY_DEBT_WORKING",
            "PROPERTY_ARREARS_30",
            "PROPERTY_ARREARS_31_60",
            "PROPERTY_ARREARS_61_90",
            "PROPERTY_ARREARS_91_180",
            "PROPERTY_ARREARS_181_365",
            "PROPERTY_ARREARS_366",
            "PROPERTY_ARREARS_TOTAL",
            "PROPERTY_PENALTIES",
            "PROPERTY_DEBT_TOTAL",
        ];
    }

    public function getHeaders()
    {
        $this->getPersonList();
        $this->getRegions();
        $this->getDeptStatuses();

        $this->arResult['HEADERS'] = [
            [
                'id' => 'ID',
                'name' => 'Компания',
                'default' => true,
                'sort' => false,
                'type' => 'dest_selector',
                'params' => [
                    'enableUsers' => 'N',
                    'allowUserSearch' => 'N',
                    'context' => 'CRM',
                    'contextCode' => 'CRM',
                    'multiple' => 'Y',
                    'enableCrm' => 'Y',
                    'enableCrmCompanies' => 'Y'
                ],
                'prefix' => '',
            ],
            [
                'id' => 'UF_CRM_1589535058',
                'name' => 'Регион',
                'default' => true,
                'type' => 'list',
                'items' => $this->regions,
                'params' => [
                    'multiple' => 'Y',
                ]
            ],
            [
                'id' => 'ASSIGNED_BY_ID',
                'sort' => false,
                'name' => 'Менеджер',
                'default' => true,
                'type' => 'dest_selector',
                'params' => [
                    'context' => 'FILTER_ASSIGNED_BY_ID',
                    'multiple' => 'Y',
                    'contextCode' => 'U',
                    'enableAll' => 'N',
                    'enableSonetgroups' => 'N',
                    'allowEmailInvitation' => 'N',
                    'allowSearchEmailUsers' => 'N',
                    'departmentSelectDisable' => 'Y',
                    'isNumeric' => 'Y'
                ],
                'prefix' => 'U',
            ],
            [
                'id' => 'UF_CRM_1593516154612',
                'name' => 'Статус задолженности',
                'default' => true,
                'sort' => 'UF_CRM_1593516154612',
                'type' => 'list',
                'items' => $this->deptStatuses
            ],

            [
                'id' => 'ORGANIZATION',
                'name' => 'Организация',
                'default' => true,
                'sort' => false,
                'type' => 'list',
                'items' => [
                    31 => 'ЮУГПК',
                    34 =>  'ГЦЗ'
                ]
            ],
            [
                'id' => 'CREDIT_TIME',
                'name' => 'Срок кредитного лимита',
                'default' => true,
                'sort' => false,
            ],
            [
                'id' => 'TITLE',
                'name' => 'Название компании',
                'default' => true,
                'sort' => false,
            ],
            [
                'id' => 'UF_CRM_1593516202',
                'name' => 'Комментарий по работе',
                'default' => true,
                'editable' => ["size" => 20, "maxlength" => 255],
                'sort' => false,
            ]

        ];

        $codes = $this->getContractProps();

        $filter = [
            'IBLOCK_ID' => self::IBLOCK_ID[0],
            "ACTIVE" => "Y",
            "CHECK_PERMISSIONS" => "N",
        ];
        $props = \CIBlockProperty::GetList([], $filter);
        while ($prop = $props->Fetch()) {
            $code = 'PROPERTY_' . $prop['CODE'];
            if (in_array($code, $codes)) {
                $this->arResult['HEADERS'][] = [
                    'id' => $code,
                    'type' => 'number',
                    'name' => $prop['NAME'],
                    'default' => true,
                    'sort' => $code,
                ];
            }
        }

        $this->arResult['FILTERS'] = array_merge(array_slice($this->arResult['HEADERS'], 0, 5), [
            [
                'id' => 'PROPERTY_DEBT_TOTAL',
                'type' => 'number',
                'name' => 'Общая сумма ДЗ',
                'default' => true,
                'sort' => false,
            ]
        ]);
    }

    public function format_row($row){
        foreach($row as $key => $val){
            if(is_numeric($val) && preg_match('/(PROPERTY_)/', $key) &&  !strpos($key, 'ID')){
                $row[$key] = number_format($val, 0, ',', ' ');
            }
        }
        return $row;
    }
    public function getData()
    {
        $grid_options = new CGridOptions($this->arResult["GRID_ID"]);
        $aSort = $grid_options->GetSorting(array("sort"=>array("PROPERTY_DEBT_TOTAL"=>"desc"), "vars"=>array("by"=>"by", "order"=>"order")));
        $this->arResult['SORT'] = $aSort['sort'];

        $this->getCompanies();
        $this->getContracts();
        
        if(count($this->filteredCompanyIds)){
            foreach($this->filteredCompanyIds as $companyId){
                if(isset($this->contracts[$companyId])){
                    $rows[] = array_merge($this->companies[$companyId], $this->contracts[$companyId]);
                }
            }
        }else{
            foreach($this->contracts as $companyId => $contract){
                $rows[] = array_merge($this->companies[$companyId], $contract);
            }
        }

        foreach($rows as $row){
            $this->arResult['ROWS'][] = ['data' => $this->format_row($row), 'columns' => $this->format_row($row)];
            foreach ($row['ITEMS'] as $subRow) {
                $this->arResult['ROWS'][] = ['data' => $this->format_row($subRow), 'columns' => $this->format_row($subRow), 'editable' => false];
            }
        }
    }

    public function getContractFilter()
    {
        $arSelect = $this->getContractProps();
        $filterOptions = new Options(self::GRID_ID);
        $filterOps = $filterOptions->getFilter();

        $arFilter = [];
        if ($filterOps) {
            foreach ($filterOps as $key => $value) {
                if (strpos($key, '_numsel')) {
                    $code = str_replace('_numsel', '', $key);
                    if (in_array($code, $arSelect)) {
                        switch ($value) {
                            case "range":
                                $arFilter[">" . $code] = $filterOps[$code . '_from'];
                                $arFilter["<" . $code] = $filterOps[$code . '_to'];
                                break;
                            case "exact":
                                $arFilter[$code] = $filterOps[$code . '_from'];
                                break;
                            case "more":
                                $arFilter[">" . $code] = $filterOps[$code . '_from'];
                                break;
                            case "less":
                                $arFilter["<" . $code] = $filterOps[$code . '_to'];
                                break;
                        }
                    }
                } else if ($key == 'ORGANIZATION' && in_array($value, self::IBLOCK_ID)) {
                    $arFilter['IBLOCK_ID'] = $value;
                }
            }
        }
        return $arFilter;
    }

    public function getDeptStatuses()
    {
        // UF_CRM_1593516154612
        $fieldDb = \CUserFieldEnum::GetList([], ['USER_FIELD_ID' => 465]);
        while ($field = $fieldDb->Fetch()) {
            $this->deptStatuses[$field['ID']] = $field['VALUE'];
        }
    }
    public function getRegions()
    {

        $org = CIBlockElement::GetList([], ['IBLOCK_ID' => self::REGION_IBLOCK_ID]);
        while ($region = $org->Fetch()) {
            $this->regions[$region['ID']] = $region['NAME'];
        }
    }

    public function getPrintRegion(array $regionId)
    {
        $result = [];
        foreach ($regionId as $id) {
            if ($this->regions[$id]) {
                $result[] = $this->regions[$id];
            } else {
                $result[] = $id;
            }
        }
        return implode(', ', $result);
    }

    public function getFilteredCompanies($companyFilter = [])
    {
        $arFilter = $this->getContractFilter();
        
        $grid_options = new CGridOptions(self::GRID_ID);
        $aNav = $grid_options->GetNavParams();

        $context = Context::getCurrent();
        $request = $context->getRequest();
        $pager = new PageNavigation('');
        $pager->setPageSize($aNav['nPageSize']);

       

        if ($request->offsetExists('page')) {
            $currentPage = $request->get('page');
            $pager->setCurrentPage($currentPage > 0 ? $currentPage : $pager->getPageCount());
        } else {
            $pager->setCurrentPage(1);
        }

        if($this->arResult['SORT']){
            $sort = array_keys($this->arResult['SORT'])[0];
            $order = $this->arResult['SORT'][$sort];
            
            $params = ['BITRIXID_COMPANY'=>'c', str_replace('PROPERTY_','',$sort)=>'p'];

            foreach($params as $param => $code){
                $query_parts = [];
                foreach(self::IBLOCK_ID as $ib){
                    $prop_fields = \CIBlockProperty::GetList([], ['CODE' => $param , 'IBLOCK_ID'=> $ib])->Fetch();
                    $query_parts[] = "{$code}.IBLOCK_PROPERTY_ID='{$prop_fields["ID"]}'";
                }
                $where_parts[] = "(".implode(' OR ', $query_parts).")";
            }
            
            $where = implode(' AND ', $where_parts);

            if($companyFilter['ID']){
                $where .= ' AND c.VALUE IN ('.implode(',',$companyFilter['ID']).')'; 
            }
            
            global $DB;
            $this->filteredCompanyIds = [];
            $query = $DB->Query("SELECT SUM(p.VALUE_NUM) as amount, c.VALUE as company_id FROM b_iblock_element_property p LEFT JOIN b_iblock_element_property c ON p.IBLOCK_ELEMENT_ID = c.IBLOCK_ELEMENT_ID WHERE $where GROUP BY company_id ORDER BY amount $order");
            while ($company = $query->fetch()) {
                $this->filteredCompanyIds[$company['company_id']] = $company['company_id'];
            }
    

            $where_debt_parts = [$where_parts[0]];
            $query_parts = [];
            foreach(self::IBLOCK_ID as $ib){
                $prop_fields = \CIBlockProperty::GetList([], ['CODE' => 'DEBT_TOTAL' , 'IBLOCK_ID'=> $ib])->Fetch();
                $query_parts[] = "p.IBLOCK_PROPERTY_ID='{$prop_fields["ID"]}'";
            }
            $where_debt_parts[] = "(".implode(' OR ', $query_parts).")";
            
            $where = implode(' AND ', $where_debt_parts);
            $query = $DB->Query("SELECT c.VALUE as company_id FROM b_iblock_element_property p LEFT JOIN b_iblock_element_property c ON p.IBLOCK_ELEMENT_ID = c.IBLOCK_ELEMENT_ID WHERE $where AND (p.VALUE_NUM = 0) GROUP BY company_id");
            while ($company = $query->fetch()) {
                if(isset($this->filteredCompanyIds[$company['company_id']])){
                    unset($this->filteredCompanyIds[$company['company_id']]);
                }
            }
            $this->filteredCompanyIds = array_values($this->filteredCompanyIds);

        }

        $arFilter['PROPERTY_BITRIXID_COMPANY'] = $this->filteredCompanyIds;

        $contracts = \CIBlockElement::GetList(['PROPERTY_BITRIXID_COMPANY'=>'DESC'], $arFilter, ['PROPERTY_BITRIXID_COMPANY'], false, ['ID']);
        $pager->setRecordCount($contracts->SelectedRowsCount());
        
        $offset = ($pager->getCurrentPage()-1) * $aNav['nPageSize'];

        $filteredCompanyIds = [];
        while ($contract = $contracts->fetch()) {
            $filteredCompanyIds[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']] = $contract['PROPERTY_BITRIXID_COMPANY_VALUE'];
        }
        $return = [];
        $ids = array_slice($this->filteredCompanyIds, $offset);
        
        foreach($ids as $i=>$companyId){
            if($filteredCompanyIds[$companyId]){
                $return[] = $filteredCompanyIds[$companyId];
            }
            if(count($return) >= $aNav['nPageSize']){
                break;
            }
        }
       
        $this->arResult['PAGINATION'] = [
            'PAGE_NUM' => $pager->getCurrentPage(),
            'ENABLE_NEXT_PAGE' => $pager->getCurrentPage() < $pager->getPageCount(),
            'URL' => $request->getRequestedPage(),
        ];

        
        return $return;
    }

    public function getCompanies()
    {
        $filterOptions = new Options(self::GRID_ID);

        $filterOps = $filterOptions->getFilter();
        $arFilter = [];

        $arCompanyFields = ['TITLE', 'ASSIGNED_BY_ID', 'UF_CRM_1589535058', 'ID', 'UF_CRM_1594324154648', 'UF_CRM_1594324132154', 'UF_CRM_1593516154612'];

        foreach ($filterOps as $key => $value) {
            if (in_array($key, $arCompanyFields) && $value != 'undefined') {
                if ($key == 'ASSIGNED_BY_ID' || $key == 'ID') {
                    $arFilter[$key] = array_map(function ($v) {
                        return (int) str_replace(['U', 'CRMCOMPANY'], '', $v);
                    }, $value);
                } else {
                    if ($key == 'TITLE') {

                        $arFilter['%TITLE'] = $value;
                    } else {
                        $arFilter[$key] = $value;
                    }
                }
            }
        }
        $filteredCompanyIds = $this->getFilteredCompanies($arFilter);
        $arFilter = array_merge($arFilter, ['=ID' => $filteredCompanyIds]);
        
       
        $arCompanies = \CCrmCompany::GetListEx([], $arFilter, false, false, array_merge($arCompanyFields, ['ID', 'TITLE', 'UF_CRM_1593516202', 'ASSIGNED_BY_ID', 'UF_CRM_1589535058']));
        while ($arItem = $arCompanies->fetch()) {
            $arItem['ASSIGNED_BY_ID'] = '<a href="/company/personal/user/' . $arItem['ASSIGNED_BY_ID'] . '/" id="BALLOON_COMPANY_6_RESPONSIBLE_U_' . $arItem['ASSIGNED_BY_ID'] . '" target="_blank" bx-tooltip-user-id="' . $arItem['ASSIGNED_BY_ID'] . '">' . $this->person[$arItem['ASSIGNED_BY_ID']] . '</a>';
            $arItem['TITLE'] = '<a href="/crm/company/details/' . $arItem['ID'] . '/" >' . $arItem['TITLE'] . '</a>';

            if ($arItem['UF_CRM_1594324154648']) {
                $arItem['CREDIT_TIME'] = $arItem['UF_CRM_1594324154648'];
            } else if ($arItem['UF_CRM_1594324132154']) {
                $arItem['CREDIT_TIME'] = $arItem['UF_CRM_1594324132154'];
            }

            $arItem['UF_CRM_1589535058'] = $this->getPrintRegion($arItem['UF_CRM_1589535058']);
            $arItem['UF_CRM_1593516154612'] = $this->deptStatuses[$arItem['UF_CRM_1593516154612']];
            $this->companies[$arItem['ID']] = $arItem;
        }
    }

    public function getContracts()
    {
        $arSelect = array_merge(["IBLOCK_ID", "PROPERTY_BITRIXID_COMPANY", "PROPERTY_CONTRACT_TITLE"], $this->getContractProps());

        $ids = array_keys($this->companies);

        if (count($ids)) {
            $arFilter = ["IBLOCK_ID" => self::IBLOCK_ID, '=PROPERTY_BITRIXID_COMPANY' => $ids, '!PROPERTY_DEBT_TOTAL'=> false];

            $contracts = \CIBlockElement::GetList(["ID" => "DESC"], $arFilter, false, false, ['ID']);
            
            while ($c = $contracts->fetch()) {
                $contract = \CIBlockElement::GetList(["ID" => "DESC"], ['=ID' => $c['ID']], false, false, array_merge($arSelect, ['NAME']))->Fetch();

                // $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['ORGANIZATION'] = $contract['IBLOCK_ID'] == 31 ? 'ЮУГПК' : 'ГЦЗ';
                // $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['TITLE'] = $contract['NAME'];
                $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['PROPERTY_CREDIT_LIMIT'] += (float) $contract['PROPERTY_CREDIT_LIMIT_VALUE'];
                $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['PROPERTY_CREDIT_TERM'] = $contract['PROPERTY_CREDIT_TERM_VALUE'];
                $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['PROPERTY_DEBT_WORKING'] += (float) $contract['PROPERTY_DEBT_WORKING_VALUE'];
                $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['PROPERTY_ARREARS_30'] += (float) $contract['PROPERTY_ARREARS_30_VALUE'];
                $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['PROPERTY_ARREARS_31_60'] += (float) $contract['PROPERTY_ARREARS_31_60_VALUE'];
                $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['PROPERTY_ARREARS_61_90'] += (float) $contract['PROPERTY_ARREARS_61_90_VALUE'];
                $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['PROPERTY_ARREARS_91_180'] += (float) $contract['PROPERTY_ARREARS_91_180_VALUE'];
                $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['PROPERTY_ARREARS_181_365'] += (float) $contract['PROPERTY_ARREARS_181_365_VALUE'];
                $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['PROPERTY_ARREARS_366'] += (float) $contract['PROPERTY_ARREARS_366_VALUE'];
                $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['PROPERTY_ARREARS_TOTAL'] += (float) $contract['PROPERTY_ARREARS_TOTAL_VALUE'];
                $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['PROPERTY_PENALTIES'] += (float) $contract['PROPERTY_PENALTIES_VALUE'];
                $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['PROPERTY_DEBT_TOTAL'] += (float) $contract['PROPERTY_DEBT_TOTAL_VALUE'];

                $item = [];
                foreach ($contract as $key => $val) {
                    $item[str_replace('_VALUE', '', $key)] = $val;
                }

                $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['ITEMS'][] = array_merge($item, ['ORGANIZATION' => $contract['IBLOCK_ID'] == 31 ? 'ЮУГПК' : 'ГЦЗ', 'TITLE' => $contract['NAME']]);
            }
        }
    }

    private function getPersonList()
    {
        $users = UserTable::GetList(['filter' => ['ACTIVE' => 'Y']]);
        if ($users) {
            while ($user = $users->Fetch()) {
                $this->person[$user['ID']] = sprintf("%s %s",  $user['NAME'],  $user['LAST_NAME'], $user['SECOND_NAME']);
            }
        }
    }
}
