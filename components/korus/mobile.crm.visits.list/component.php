<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();


if (!CModule::IncludeModule('crm')) {
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if (!\CAllCrmDeal::IsAccessEnabled()) {
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult["IS_CREATE_PERMITTED"] = true;

if (!isset($arParams['GRID_ID']) || $arParams['GRID_ID'] === '') {
	$arParams['GRID_ID'] = 'mobile_crm_visits_list';
}

$gridOptions = CUserOptions::GetOption("mobile.interface.grid", $arParams["GRID_ID"]);

//sort
$sort = array('DATE_CREATE' => 'desc');
if (isset($gridOptions["sort_by"]) && isset($gridOptions["sort_order"]))
	$sort = array($gridOptions["sort_by"] => $gridOptions["sort_order"]);

//select
$commonSelect = array(
	'NAME',
	'ID',
	'PROPERTY_VIZIT_SOVERSHIL_I',
);



if (isset($gridOptions["fields"]) && is_array($gridOptions["fields"]))
	$commonSelect = $gridOptions["fields"];

$select = $commonSelect;

if (!in_array("ID", $select)) {
	$select[] = "ID";
}

if (!in_array("NAME", $select)) {
	$select[] = "NAME";
	$commonSelect[] = "NAME";
}


if (isset($sort["DATE_CREATE"]) && !in_array("DATE_CREATE", $select))
	$select[] = "DATE_CREATE";

if (isset($sort["DATE_MODIFY"]) && !in_array("DATE_MODIFY", $select))
	$select[] = "DATE_MODIFY";

if (in_array("ASSIGNED_BY", $select))
	$select = array_merge($select, array('ASSIGNED_BY_ID', 'ASSIGNED_BY_LOGIN', 'ASSIGNED_BY_NAME', 'ASSIGNED_BY_SECOND_NAME', 'ASSIGNED_BY_LAST_NAME'));

if (in_array("CREATED_BY", $select))
	$select = array_merge($select, array('CREATED_BY_ID', 'CREATED_BY_LOGIN', 'CREATED_BY_NAME', 'CREATED_BY_SECOND_NAME', 'CREATED_BY_LAST_NAME'));

if (in_array("MODIFY_BY", $select))
	$select = array_merge($select, array('MODIFY_BY_ID', 'MODIFY_BY_LOGIN', 'MODIFY_BY_NAME', 'MODIFY_BY_SECOND_NAME', 'MODIFY_BY_LAST_NAME'));

//filter
$filter = array(
	'IBLOCK_ID' => $arParams['IBLOCK_ID']
);

if (isset($_REQUEST["search"])) {
	CUtil::JSPostUnescape();
	$v = trim($_REQUEST["search"]);
	if (!empty($v)) {
		$searchFilter = array(
			'IBLOCK_ID' => $arParams['IBLOCK_ID'],
			'%NAME' => $v,
			'LOGIC' => 'OR'
		);
		if (!empty($filter)) {
			$filter["__INNER_FILTER"] = $searchFilter;
		} else {
			$filter = $searchFilter;
		}
	}
}

$arResult['FILTER_PRESETS'] = array(
	'all' => array('name' => GetMessage('M_CRM_VISITS_LIST_FILTER_NONE'), 'fields' => []),
	'filter_new' => array('name' => GetMessage('M_CRM_VISITS_LIST_PRESET_NEW'), 'fields' => array('STAGE_ID' => array('NEW'))),
	'filter_my' => array('name' => GetMessage('M_CRM_VISITS_LIST_PRESET_MY'), 'fields' => array('ASSIGNED_BY_ID' => intval(CCrmSecurityHelper::GetCurrentUserID()))),
	'filter_user' => array('name' => GetMessage('M_CRM_LEAD_LIST_PRESET_USER'), 'fields' => [])
);

if (isset($gridOptions['filters']['filter_user'])) {
	foreach ($gridOptions['filters']['filter_user']['fields'] as $field => $value) {
		if ($value !== "")
			$arResult['FILTER_PRESETS']['filter_user']['fields'][$field] = $value;
	}
}

$arResult["CURRENT_FILTER"] = "all";
if (isset($gridOptions["currentFilter"]) && in_array($gridOptions["currentFilter"], array_keys($arResult['FILTER_PRESETS']))) {
	$filter = array_merge($filter, $arResult['FILTER_PRESETS'][$gridOptions["currentFilter"]]['fields']);
	$arResult["CURRENT_FILTER"] = $gridOptions["currentFilter"];

	if (isset($filter['NAME'])) {
		$filter['%NAME'] = $filter['NAME'];
		unset($filter['NAME']);
	}

	if (isset($filter['DATE_CREATE'])) {
		$filter['>=DATE_CREATE'] = $filter['DATE_CREATE'];
		$filter['<=DATE_CREATE'] = CCrmDateTimeHelper::SetMaxDayTime($filter['DATE_CREATE']);
		unset($filter['DATE_CREATE']);
	}
}

//navigation
$itemPerPage = isset($arParams['ITEM_PER_PAGE']) ? intval($arParams['ITEM_PER_PAGE']) : 0;
if ($itemPerPage <= 0) {
	$itemPerPage = 20;
}
$navParams = array(
	'nPageSize' => $itemPerPage,
	'iNumPage' => true,
	'bShowAll' => false
);
$navigation = CDBResult::GetNavParams($navParams);
$CGridOptions = new CGridOptions($arParams["GRID_ID"]);
$navParams = $CGridOptions->GetNavParams($navParams);

$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : SITE_DIR . 'mobile/users/?user_id=#user_id#';
$arParams['VISITS_VIEW_URL_TEMPLATE'] = isset($arParams['VISITS_VIEW_URL_TEMPLATE']) ? $arParams['VISITS_VIEW_URL_TEMPLATE'] : SITE_DIR . '/mobile/crm/visits/?page=view&visits_id=#visits_id#';
$arParams['VISITS_EDIT_URL_TEMPLATE'] = isset($arParams['VISITS_EDIT_URL_TEMPLATE']) ? $arParams['VISITS_EDIT_URL_TEMPLATE'] : SITE_DIR . '/mobile/crm/visits/?page=edit&visits_id=#visits_id#';
$arParams['VISITS_CREATE_URL_TEMPLATE'] = isset($arParams['VISITS_CREATE_URL_TEMPLATE']) ? $arParams['VISITS_CREATE_URL_TEMPLATE'] : SITE_DIR . '/mobile/crm/visits/?page=edit';
$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);

$arResult["AJAX_PATH"] = '/mobile/?mobile_action=mobile_crm_visits_actions';

// $finalStageID = CCrmDeal::GetFinalStageID();
// $finalStageSort = CCrmDeal::GetFinalStageSort();


//fields to show
$arResult["FIELDS"] = [];

$allFields = [
	'PROPERTY_VIZIT_SOVERSHIL_I' => ['ID' => 'PROPERTY_VIZIT_SOVERSHIL_I', 'NAME' => 'Визит совершили']
];

$CIBlockProperties = [];

$fields = CIBlock::GetProperties($arParams['IBLOCK_ID']);
while ($field = $fields->fetch()) {
	$allFields['PROPERTY_' . $field['CODE']] = [
		'id' => 'PROPERTY_' . $field['CODE'],
		'name' => $field['NAME'],
	];

	$CIBlockProperties['PROPERTY_' . $field['CODE']] = $field;
}


$checkBoxUserFields = [];
if (!empty($userFields)) {
	foreach ($userFields as $fieldId => $info) {
		if ($info['type'] == 'CHECKBOX') {
			$checkBoxUserFields[] = $fieldId;
		}
	}
}

foreach ($commonSelect as $code) {
	$arResult["FIELDS"][$code] = $allFields[$code];
}

$arResult['ITEMS'] = [];

$dbRes = CIBlockElement::GetList($sort, $filter, false, $navParams, array_merge($select, array_keys($allFields)), []);
$dbRes->NavStart($navParams['nPageSize'], false);

$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
$arResult["NAV_PARAM"] = array(
	'PAGER_PARAM' => "PAGEN_{$arResult['PAGE_NAVNUM']}",
	'PAGE_NAVCOUNT' => intval($dbRes->NavPageCount),
	'PAGE_NAVNUM' => intval($dbRes->NavNum),
	'PAGE_NUMBER' => intval($dbRes->NavPageNomer)
);

$enums = array(
	'FIELDS' => array_keys($arResult["FIELDS"]),
	'CHECKBOX_USER_FIELDS' => $checkBoxUserFields
);

while ($item = $dbRes->Fetch()) {

	$isEditPermitted = CCrmDeal::CheckUpdatePermission($item['ID'], $userPerms);
	$isDeletePermitted = CCrmDeal::CheckDeletePermission($item['ID'], $userPerms);

	$enums['IS_EDIT_PERMITTED'] = $isEditPermitted;

	// $categoryID = isset($item['~CATEGORY_ID']) ? (int)$item['~CATEGORY_ID'] : CCrmDeal::GetCategoryID($item["ID"]);
	// $stageList = CCrmViewHelper::GetDealStageInfos($categoryID);

	// $jsStageList = [];
	// $i=0;
	// foreach($stageList as $id => $info)
	// {
	// 	if (!isset($info["COLOR"]))
	// 	{
	// 		$semanticId = \CAllCrmDeal::GetSemanticID($info["STATUS_ID"]);

	// 		if ($semanticId == \Bitrix\Crm\PhaseSemantics::PROCESS)
	// 			$info["COLOR"] = \CCrmViewHelper::PROCESS_COLOR;
	// 		else if ($semanticId == \Bitrix\Crm\PhaseSemantics::FAILURE)
	// 			$info["COLOR"] = \CCrmViewHelper::FAILURE_COLOR;
	// 		else if ($semanticId == \Bitrix\Crm\PhaseSemantics::SUCCESS)
	// 			$info["COLOR"] = \CCrmViewHelper::SUCCESS_COLOR;

	// 		$stageList[$id]['COLOR'] = $info["COLOR"];
	// 	}

	// 	$jsStageList["s".$i] = array(
	// 		"STATUS_ID" => $info["STATUS_ID"],
	// 		"NAME" => htmlspecialcharsbx($info["NAME"]),
	// 		"COLOR" => $info["COLOR"]
	// 	);
	// 	$i++;
	// }
	// $enums["STAGE_LIST"] = $stageList;
	// $enums["JS_STAGE_LIST"] = $jsStageList;

	// // try to load product rows
	// if (in_array("PRODUCT_ID", $select))
	// {
	// 	$item["PRODUCT_ID"] = [];
	// 	$arProductRows = CCrmDeal::LoadProductRows($item['ID']);
	// 	foreach($arProductRows as $arProductRow)
	// 	{
	// 		$item["PRODUCT_ID"][] = $arProductRow["PRODUCT_NAME"];
	// 	}
	// 	$item["PRODUCT_ID"] = implode(", ", $item["PRODUCT_ID"]);
	// }

	//CCrmMobileHelper::PrepareDealItem($item, $arParams, $enums);
	// $item = VisitsHelper::prepare($item, $arParams, $enums);


	$arActions = [];

	// if ($isEditPermitted)
	// {
	// 	$arActions[] = array(
	// 		"TEXT" => GetMessage("M_CRM_VISITS_LIST_CHANGE_STAGE"),
	// 		"ONCLICK" => "BX.Mobile.Crm.List.showStatusList(" . $item['ID'] . ", " . CUtil::PhpToJSObject(
	// 			$jsStageList) . ", 'onCrmDealDetailUpdate')",
	// 	);

	// 	$canConvert = [];
	// 	CCrmDeal::PrepareConversionPermissionFlags($item['ID'], $canConvert, $userPerms);

	// 	if ($canConvert["CONVERSION_PERMITTED"])
	// 	{
	// 		$arActions[] = array(
	// 			'TEXT' => GetMessage("M_CRM_VISITS_LIST_CREATE_BASE"),
	// 			'ONCLICK' => "BX.Mobile.Crm.Deal.ListConverter.showConvertDialog('" . $item['ID'] . "', " . CUtil::PhpToJSObject($canConvert) . ");",
	// 			'DISABLE' => false
	// 		);
	// 	}
	// }


	$buttons = "";


	if ($isEditPermitted) {
		$detailEditUrl = CComponentEngine::MakePathFromTemplate(
			$arParams['VISITS_EDIT_URL_TEMPLATE'],
			array('visits_id' => $item['ID'])
		);

		$buttons .= "{
						title:'" . GetMessageJS("M_CRM_VISITS_LIST_EDIT") . "',
						callback:function()
						{
							BXMobileApp.PageManager.loadPageModal({
								url: '" . CUtil::JSEscape($detailEditUrl) . "'
							});
						}
					},";
	}
	if ($isDeletePermitted) {
		$buttons .= "{
						title:'" . GetMessageJS("M_CRM_VISITS_LIST_DELETE") . "',
						callback:function()
						{
							BX.Mobile.Crm.deleteItem('" . $item["ID"] . "', '" . $arResult["AJAX_PATH"] . "', 'list');
						}
					}";
	}



	if (!empty($buttons)) {
		$arActions[] = array(
			"TEXT" => GetMessage("M_CRM_VISITS_LIST_MORE"),
			'ONCLICK' => "new BXMobileApp.UI.ActionSheet({
							buttons: [" . $buttons . "]
						}, 'actionSheet').show();",
			'DISABLE' => false
		);
	}
	$detailViewUrl = CComponentEngine::MakePathFromTemplate(
		$arParams['VISITS_VIEW_URL_TEMPLATE'],
		array('visits_id' => $item['ID'])
	);

	foreach ($item as $key => $value) {
		if (preg_match('`(_VALUE)$`', $key)) {
			$newKey = preg_replace('`(_VALUE)$`', '', $key);
			$item[$newKey] = $value;
			unset($item[$key]);
		}
	}


	foreach ($item as $key => $value) {
		if (isset($CIBlockProperties[$key])) {
			if ($CIBlockProperties[$key]['USER_TYPE'] == 'ECrm') {
				$type = false;
				foreach($CIBlockProperties[$key]['USER_TYPE_SETTINGS'] as $utsType => $v){
					if(in_array($utsType, ['COMPANY','CONTACT','DEAL']) && $v == 'Y'){
						$type = $utsType;
					}
				}
				switch($type){
					case "COMPANY":
						if ($value > 0 && \CCrmCompany::CheckReadPermission($value))
						{
							$url = CComponentEngine::MakePathFromTemplate(
								$arParams['COMPANY_SHOW_URL_TEMPLATE'],
								array('company_id' => $value)
							);
							if($company = CCrmCompany::GetByID($value)){
								$item[$key] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".htmlspecialchars($company['TITLE'])."</span>";
							}
						}
					break;
					case "CONTACT":
						if ($value > 0 && \CCrmContact::CheckReadPermission($value))
						{
							$url = CComponentEngine::MakePathFromTemplate(
								$arParams['CONTACT_SHOW_URL_TEMPLATE'],
								array('contact_id' => $value)
							);
							
							if($contact = CCrmContact::GetByID($value)){
								$item[$key] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".$contact['NAME'].' '.$contact['LAST_NAME']."</span>";
							}
						}
					break;
					case "DEAL":
						if ($value > 0 && \CCrmDeal::CheckReadPermission($value))
						{
							$url = CComponentEngine::MakePathFromTemplate(
								$arParams['DEAL_SHOW_URL_TEMPLATE'],
								array('deal_id' => $value)
							);
							if($deal = CCrmDeal::GetByID($value)){
								$item[$key] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".htmlspecialchars($deal['TITLE'])."</span>";
							}
						}
					break;
				}

			}else if ($CIBlockProperties[$key]['USER_TYPE'] == 'employee') {

				if ($user = \CUser::GetByID($value)->Fetch()) {

					$item[$key . '_ID'] = $value;
					$item['~' . $key . '_LOGIN'] = $user['LOGIN'];
					$item['~' . $key . '_NAME'] = $user['NAME'];
					$item['~' . $key . '_LAST_NAME'] = $user['LAST_NAME'];
					$item['~' . $key . '_SECOND_NAME'] = $user['SECOND_NAME'];
					CCrmMobileHelper::PrepareUserLink($item, $key, $params);
				}
			}
		}
	}


	$arResult['ITEMS'][$item['ID']] = array(
		"TITLE" => $item["NAME"],
		"ACTIONS" => $arActions,
		"FIELDS" => $item,
		"ICON_HTML" => '<span class="mobile-grid-field-title-icon" ' . (!isset($arResult["FIELDS"]["STAGE_ID"]) ? 'style="background:' . $arResult['STAGE_LIST'][$curStageId]["COLOR"] . '"' : "") . '>
							<img src="' . $this->getPath() . '/images/icon-deal.png" srcset="' . $this->getPath() . '/images/icon-deal.png 2x" alt="">
						</span>',
		"ONCLICK" => "BX.Mobile.Crm.loadPageBlank('" . CUtil::JSEscape($detailViewUrl) . "');",
		"DATA_ID" => "mobile-grid-item-" . $item["ID"]
	);
}

//date separators for grid
if (isset($sort["DATE_CREATE"]) && in_array("DATE_CREATE", $select) || isset($sort["DATE_MODIFY"]) && in_array("DATE_MODIFY", $select)) {
	$dateSortField = isset($sort["DATE_CREATE"]) ? "DATE_CREATE" : "DATE_MODIFY";
	CCrmMobileHelper::prepareDateSeparator($dateSortField, $arResult['ITEMS']);
}

$this->IncludeComponentTemplate();
