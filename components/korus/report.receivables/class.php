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

    private $arCompanySortFields = [
        'PROPERTY_DEBT_TOTAL'=>'UF_CRM_1594054337',
        'PROPERTY_ARREARS_TOTAL'=>'UF_CRM_1594054142',
        'PROPERTY_CREDIT_LIMIT_DAYS' => 'UF_CRM_1594399508569',
        'PROPERTY_DEBT_WORKING' => 'UF_CRM_1594054087',
        'PROPERTY_CREDIT_LIMIT' => 'UF_CRM_1606804522'
    ];

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
        if (count($fields)) {
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
            "PROPERTY_CREDIT_LIMIT_DAYS",
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
                'id' => 'TITLE',
                'name' => 'Название компании',
                'default' => true,
                'sort' => true,
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
                    'sort' => isset($this->arCompanySortFields[$code])? $code : false,
                ];
            }
        }

        $this->arResult['FILTERS'] = array_merge(array_slice($this->arResult['HEADERS'], 0, 5), [
            [
                'id' => 'UF_CRM_1594054337',
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
        $aSort = $grid_options->GetSorting(array("sort"=>array("UF_CRM_1594054337"=>"desc"), "vars"=>array("by"=>"by", "order"=>"order")));

        $this->arResult['SORT'] = $aSort['sort'];

        $this->getCompanies();
        $this->getContracts();


        foreach($this->companies as $row){
            $this->arResult['ROWS'][] = ['data' => $this->format_row(array_merge($row,$this->contracts[$row['ID']])) , 'columns' => $this->format_row($row)];
            foreach ($this->contracts[$row['ID']]['ITEMS'] as $subRow) {
                $this->arResult['ROWS'][] = ['data' => $this->format_row($subRow), 'columns' => $this->format_row($subRow), 'editable' => false];
            }
        }
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


    public function getCompanies()
    {
        $filterOptions = new Options(self::GRID_ID);

        $filterOps = $filterOptions->getFilter();
        $arFilter = ['>=UF_CRM_1594054337' => 0.5];

        $arCompanyFields = ['TITLE', 'ASSIGNED_BY_ID', 'UF_CRM_1589535058', 'ID', 'UF_CRM_1594324154648', 'UF_CRM_1594324132154', 'UF_CRM_1593516154612', 'UF_CRM_1594054337'];

        foreach ($filterOps as $key => $value) {
            if ($value != 'undefined') {
                if ($key == 'ORGANIZATION') {
                    // ORGANIZATION
                    if(in_array($value, self::IBLOCK_ID)){
                        $orgFilter = ["IBLOCK_ID" => $value, '>=PROPERTY_DEBT_TOTAL'=> 0.5];   
                        $contracts = \CIBlockElement::GetList(["PROPERTY_BITRIXID_COMPANY" => "DESC"], $orgFilter, false, ['nPageSize'=>100000], ['PROPERTY_BITRIXID_COMPANY']);
                        while($contract = $contracts->Fetch()){
                            $arFilter['=ID'][] = $contract['PROPERTY_BITRIXID_COMPANY_VALUE'];
                        }
                        $arFilter['=ID'] = array_unique($arFilter['=ID']);
                    }

                }else if ($key == 'ASSIGNED_BY_ID' || $key == 'ID') {
                    $arFilter[$key] = array_map(function ($v) {
                        return (int) str_replace(['U', 'CRMCOMPANY'], '', $v);
                    }, $value);
                } else {
                    if ($key == 'TITLE') {
                        $arFilter['%TITLE'] = $value;
                    } else if (strpos($key, '_numsel')) {
                        $code = str_replace('_numsel', '', $key);
                        if (in_array($code, $arCompanyFields)) {
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
                    } else if(in_array($key, $arCompanyFields)){
                        $arFilter[$key] = $value;
                    }
                }
            }
        }


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
            $currentPage = 1;
            $pager->setCurrentPage($currentPage);
        }

        $sort = [];

        foreach ($this->arResult['SORT'] as $key => $value) {
           if(in_array($key, $arCompanyFields)){
               $sort[$key] = $value;
            }else if(isset($this->arCompanySortFields[$key])){
                $sort[$this->arCompanySortFields[$key]] = $value;
           }
        }

        
        $arCompanies = \CCrmCompany::GetListEx($sort, $arFilter, false, ['iNumPage' => $currentPage, 'nPageSize'=>$aNav['nPageSize']], array_merge($arCompanyFields, ['ID', 'TITLE', 'UF_CRM_1593516202', 'ASSIGNED_BY_ID', 'UF_CRM_1589535058','UF_CRM_1594399508569']));
        $arTotal = $arCompanies->SelectedRowsCount();
        $pager->setRecordCount($arTotal);
        $this->companiesIds = [];
        while ($arItem = $arCompanies->fetch()) {
            $arItem['ASSIGNED_BY_ID'] = '<a href="/company/personal/user/' . $arItem['ASSIGNED_BY_ID'] . '/" id="BALLOON_COMPANY_6_RESPONSIBLE_U_' . $arItem['ASSIGNED_BY_ID'] . '" target="_blank" bx-tooltip-user-id="' . $arItem['ASSIGNED_BY_ID'] . '">' . $this->person[$arItem['ASSIGNED_BY_ID']] . '</a>';
            $arItem['TITLE'] = '<a href="/crm/company/details/' . $arItem['ID'] . '/" >' . $arItem['TITLE'] . '</a>';

            // $arItem['PROPERTY_CREDIT_LIMIT_DAYS'] = array_sum($arItem['UF_CRM_1594399508569']);
            $arItem['UF_CRM_1589535058'] = $this->getPrintRegion($arItem['UF_CRM_1589535058']);
            $arItem['UF_CRM_1593516154612'] = $this->deptStatuses[$arItem['UF_CRM_1593516154612']];
            $this->companiesIds[] = $arItem['ID'];
            $this->companies[] = $arItem;
        }
        
        $this->arResult['PAGINATION'] = [
            'TOTAL' => $arTotal,
            'PAGE_NUM' => $pager->getCurrentPage(),
            'ENABLE_NEXT_PAGE' => $pager->getCurrentPage() < $pager->getPageCount(),
            'URL' => $request->getRequestedPage(),
        ];
    }

    public function getContracts()
    {
        $arSelect = array_merge(["IBLOCK_ID", "PROPERTY_BITRIXID_COMPANY", "PROPERTY_CONTRACT_TITLE"], $this->getContractProps());


        if (count($this->companiesIds)) {
            $arFilter = ["IBLOCK_ID" => self::IBLOCK_ID, '=PROPERTY_BITRIXID_COMPANY' => $this->companiesIds, '>=PROPERTY_DEBT_TOTAL'=> 0.5];
            

            $filterOptions = new Options(self::GRID_ID);
            $filterOps = $filterOptions->getFilter();
            if($filterOps['ORGANIZATION'] &&  in_array($filterOps['ORGANIZATION'], self::IBLOCK_ID)){
                $arFilter['IBLOCK_ID'] = $filterOps['ORGANIZATION'];
            }

            $contracts = \CIBlockElement::GetList(["ID" => "DESC"], $arFilter, false, false, ['ID']);
            
            while ($c = $contracts->fetch()) {
                $contract = \CIBlockElement::GetList(["ID" => "DESC"], ['=ID' => $c['ID']], false, false, array_merge($arSelect, ['NAME']))->Fetch();

                // $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['ORGANIZATION'] = $contract['IBLOCK_ID'] == 31 ? 'ЮУГПК' : 'ГЦЗ';
                // $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['TITLE'] = $contract['NAME'];
                $this->contracts[$contract['PROPERTY_BITRIXID_COMPANY_VALUE']]['PROPERTY_CREDIT_LIMIT_DAYS'] += (float) $contract['PROPERTY_CREDIT_LIMIT_DAYS_VALUE'];
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
