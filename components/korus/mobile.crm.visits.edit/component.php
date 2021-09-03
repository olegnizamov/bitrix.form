<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm')) {
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}
if (!CModule::IncludeModule('mobile')) {
	ShowError(GetMessage('CRM_MOBILE_MODULE_NOT_INSTALLED'));
	return;
}

CModule::IncludeModule('fileman');

if (IsModuleInstalled('bizproc') && !CModule::IncludeModule('bizproc')) {
	ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
	return;
}


global $USER_FIELD_MANAGER, $DB, $USER;
$CCrmDeal = new CCrmDeal();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmDeal::$sUFEntityID);
$CCrmBizProc = new CCrmBizProc('VISITS');
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $arParams["NAME_TEMPLATE"]);

$isEditMode = false;
$isCopyMode = false;
$bVarsFromForm = false;
$entityID = $arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
if ($entityID <= 0 && isset($_REQUEST['visits_id'])) {
	$entityID = $arParams['ELEMENT_ID'] = intval($_REQUEST['visits_id']);
}
$arResult['ELEMENT_ID'] = $entityID;

if (!empty($arParams['ELEMENT_ID']))
	$isEditMode = true;



//region Category
//endregion

$isConverting = false;


$arResult["IS_EDIT_PERMITTED"] = false;
$arResult["IS_VIEW_PERMITTED"] = false;
$arResult["IS_DELETE_PERMITTED"] = CCrmDeal::CheckDeletePermission($arParams['ELEMENT_ID'], $userPermissions);

if ($isEditMode) {
	$arResult["IS_EDIT_PERMITTED"] = CCrmDeal::CheckUpdatePermission($arParams['ELEMENT_ID'], $userPermissions);
	if (!$arResult["IS_EDIT_PERMITTED"] && $arParams["RESTRICTED_MODE"]) {
		$arResult["IS_VIEW_PERMITTED"] = CCrmDeal::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
	}
} elseif ($isCopyMode) {
	$arResult["IS_VIEW_PERMITTED"] = CCrmDeal::CheckReadPermission($arParams['ELEMENT_ID'], $userPermissions);
} else {
	$arResult["IS_EDIT_PERMITTED"] = CCrmDeal::CheckCreatePermission($userPermissions);
}

if (!$arResult["IS_EDIT_PERMITTED"] && !$arResult["IS_VIEW_PERMITTED"]) {
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arEntityAttr = $arParams['ELEMENT_ID'] > 0
	? $userPermissions->GetEntityAttr('VISITS', array($arParams['ELEMENT_ID']))
	: array();



$requisiteIdLinked = 0;
$bankDetailIdLinked = 0;

$fields = CIBlock::GetProperties($arParams['IBLOCK_ID']);
$CIBlockProperties = [];
while ($field = $fields->fetch()) {
	$CIBlockProperties['PROPERTY_' . $field['CODE']] = $field;
}

$arFields = null;
if ($isEditMode) {
	$arResult['MODE'] = $arParams["RESTRICTED_MODE"] ? 'VIEW' : 'EDIT';

	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID'],
		'PERMISSION' => $arParams["RESTRICTED_MODE"] ? 'READ' : 'WRITE'
	);
	$arFields = CIBlockElement::GetList(array(), $arFilter, false, false, array_merge(['*'], array_keys($CIBlockProperties)))->Fetch();
	foreach ($arFields as $key => $value) {
		if (preg_match('`(_VALUE)$`', $key)) {
			$newKey = preg_replace('`(_VALUE)$`', '', $key);
			$arFields[$newKey] = $value;
			unset($arFields[$key]);
		}
	}




	if (!is_array($arFields)) {
		ShowError(GetMessage('CRM_VISITS_EDIT_NOT_FOUND', array("#ID#" => $arParams['ELEMENT_ID'])));
		return;
	}

	if ($arFields === false) {
		$isEditMode = false;
		$isCopyMode = false;
	}
} else {
	$arResult['MODE'] = 'CREATE';
	$arFields = array('ID' => 0);
}



$isExternal = $isEditMode && isset($arFields['ORIGINATOR_ID']) && isset($arFields['ORIGIN_ID']) && intval($arFields['ORIGINATOR_ID']) > 0 && intval($arFields['ORIGIN_ID']) > 0;

$arResult['ELEMENT'] = is_array($arFields) ? $arFields : null;
unset($arFields);


$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_VISITS_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_VISITS_LIST_V12';

$productDataFieldName = $arResult["productDataFieldName"] = 'VISITS_PRODUCT_DATA';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid() && $arResult["IS_EDIT_PERMITTED"]) {
	if (!$isEditMode) {
		$originatorId = isset($_POST['EXTERNAL_SALE_ID']) ? (int)$_POST['EXTERNAL_SALE_ID'] : 0;
		$originId = isset($_POST['SYNC_ORDER_ID']) ? (int)$_POST['SYNC_ORDER_ID'] : 0;
	} else {
		$originatorId = (int)$arResult['ELEMENT']['ORIGINATOR_ID'];
		$originId = (int)$arResult['ELEMENT']['ORIGIN_ID'];
	}

	if ($originId > 0 && !isset($_POST['apply'])) {
		//Emulation of "Apply" button click for sale order popup.
		$_POST['apply'] = $_REQUEST['apply'] = 'Y';
	}


	$bVarsFromForm = true;
	if (isset($_POST['save']) || isset($_POST['continue'])) {
		CUtil::JSPostUnescape();

		$arFields = $_POST;
		$arFields['IBLOCK_ID'] = $arParams['IBLOCK_ID'];
		$ID = $arResult['ELEMENT']['ID'];

		$CIBlockElement = new \CIBlockElement;
		if (!$ID) {
			$ID = $CIBlockElement->Add($arFields);
			if (!$ID) {
				$arResult['ERROR_MESSAGE'] = $CIBlockElement->LAST_ERROR;
			}
		} else {
			if (!$CIBlockElement->Update($ID, $arFields)) {
				$arResult['ERROR_MESSAGE'] = $CIBlockElement->LAST_ERROR;
			}
		}


		if (!$arResult['ERROR_MESSAGE']) {
			foreach ($arFields as $key => $value) {
				if (mb_strpos($key, 'PROPERTY_') !== false) {
					$code = str_replace('PROPERTY_', '', $key);
					\CIBlockElement::SetPropertyValueCode($ID, $code, $value);
				}
			}
		}

		$arResult['ELEMENT']['ID'] = $ID;


		$arJsonData = array();
		if (!empty($arResult['ERROR_MESSAGE'])) {
			$arJsonData = array("error" => str_replace("<br>", "\n", preg_replace("/<br( )?(\/)?>/i", "\n", $arResult['ERROR_MESSAGE'])));
		} else {
			$arJsonData = array("success" => "Y", "itemId" => $ID);
		}


		$APPLICATION->RestartBuffer();
		echo \Bitrix\Main\Web\Json::encode($arJsonData);
		CMain::FinalActions();
		die();
	}
}

$arResult['BACK_URL'] = $conversionWizard !== null && $conversionWizard->hasOriginUrl()
	? $conversionWizard->getOriginUrl() : $arParams['PATH_TO_VISITS_LIST'];


$arResult['EDIT'] = $isEditMode;

$arResult['VISITS_VIEW_PATH'] = CComponentEngine::MakePathFromTemplate(
	$arParams['VISITS_VIEW_URL_TEMPLATE'],
	array('visits_id' => $entityID)
);
$arResult['VISITS_EDIT_PATH'] = CComponentEngine::MakePathFromTemplate(
	$arParams['VISITS_EDIT_URL_TEMPLATE'],
	array('visits_id' => $entityID)
);
/*============= fields for main.interface.form =========*/
$arResult['FIELDS'] = array();


// $arResult['FIELDS'][] = array(
// 	'id' => 'ASSIGNED_BY_ID',
// 	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
// 	'type' => $arResult["IS_EDIT_PERMITTED"] ? 'select-user' : 'user',
// 	'canDrop' => false,
// 	"item" => CMobileHelper::getUserInfo(isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()),
// 	'value' => isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()
// );

// if (CCrmContact::CheckReadPermission($arResult['ELEMENT']['CONTACT_ID'], $userPermissions))
// {
// 	$arResult['ELEMENT_CONTACT'] = "";
// 	if ($arResult['ELEMENT']['CONTACT_ID'])
// 	{
// 		$contactShowUrl = CComponentEngine::MakePathFromTemplate($arParams['CONTACT_SHOW_URL_TEMPLATE'],
// 			array('contact_id' => $arResult['ELEMENT']['CONTACT_ID'])
// 		);

// 		if (!$arResult['ELEMENT']["CONTACT_FULL_NAME"])
// 		{
// 			$dbContact = CCrmContact::GetListEx(array(), array("ID" => $arResult['ELEMENT']['CONTACT_ID']), false, false, array('HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'POST', 'PHOTO'));
// 			if ($arContact = $dbContact->Fetch())
// 			{
// 				$arResult['ELEMENT']["CONTACT_FULL_NAME"] = CCrmContact::PrepareFormattedName(
// 					array(
// 						'HONORIFIC' => $arContact['HONORIFIC'],
// 						'NAME' => $arContact['NAME'],
// 						'LAST_NAME' => $arContact['LAST_NAME'],
// 						'SECOND_NAME' => $arContact['SECOND_NAME']
// 					)
// 				);

// 				$arResult['ELEMENT']["CONTACT_POST"] = $arContact["POST"];
// 				$arResult['ELEMENT']["CONTACT_PHOTO"] = $arContact["PHOTO"];
// 			}
// 		}

// 		$photoD = isset($arResult['ELEMENT']["CONTACT_PHOTO"]) ? $arResult['ELEMENT']["CONTACT_PHOTO"] : 0;
// 		if($photoD > 0)
// 		{
// 			$listImageInfo = CFile::ResizeImageGet(
// 				$photoD, array('width' => 43, 'height' => 43), BX_RESIZE_IMAGE_PROPORTIONAL );
// 			$arResult['ELEMENT']["CONTACT_PHOTO"] = $listImageInfo["src"];
// 		}
// 		$arResult['ELEMENT']["CONTACT_MULTI_FIELDS"] = CCrmMobileHelper::PrepareMultiFieldsData($arResult['ELEMENT']['CONTACT_ID'], CCrmOwnerType::ContactName);

// 		$arResult['ELEMENT_CONTACT'] = array(
// 			"id" => $arResult['ELEMENT']["CONTACT_ID"],
// 			"name" => $arResult['ELEMENT']["CONTACT_FULL_NAME"],
// 			"image" => $arResult['ELEMENT']["CONTACT_PHOTO"],
// 			"url" => $contactShowUrl,
// 			"entityType" => "contact",
// 			"addTitle" => $arResult['ELEMENT']['CONTACT_POST'],
// 			"multi" => is_array($arResult['ELEMENT']["CONTACT_MULTI_FIELDS"]) ? $arResult['ELEMENT']["CONTACT_MULTI_FIELDS"] : array()
// 		);
// 	}

// 	$arResult["ON_SELECT_CONTACT_EVENT_NAME"] = "onCrmContactSelectForDeal_".$arParams['ELEMENT_ID'];

// 	$contactPath = CHTTP::urlAddParams($arParams['CONTACT_SELECTOR_URL_TEMPLATE'], array(
// 		"event" => $arResult["ON_SELECT_CONTACT_EVENT_NAME"]
// 	));

// 	if (!$arParams["RESTRICTED_MODE"] || $arResult['ELEMENT']['CONTACT_ID'])
// 	{
// 		$arResult['FIELDS'][] = array(
// 			'id' => 'CONTACT_ID',
// 			'name' => GetMessage('CRM_FIELD_CONTACT_ID'),
// 			'type' => 'custom',
// 			'value' => '<div class="mobile-grid-field-select-user">
// 							<div id="mobile-crm-deal-edit-contact" data-role="mobile-crm-deal-edit-contact">'.
// 							//Contact's html is generated on javascript, object BX.Mobile.Crm.ClientEditor
// 							'</div>' . ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\''.CUtil::JSEscape($contactPath).'\')">'.GetMessage("CRM_BUTTON_SELECT").'</a>') .
// 						'</div>'
// 		);
// 	}
// }
// if (CCrmCompany::CheckReadPermission($arResult['ELEMENT']['COMPANY_ID'], $userPermissions))
// {
// 	$arResult['ELEMENT_COMPANY'] = "";
// 	if ($arResult['ELEMENT']['COMPANY_ID'])
// 	{
// 		$companyShowUrl = CComponentEngine::MakePathFromTemplate($arParams['COMPANY_SHOW_URL_TEMPLATE'],
// 			array('company_id' => $arResult['ELEMENT']['COMPANY_ID'])
// 		);

// 		if (!$arResult['ELEMENT']["COMPANY_TITLE"])
// 		{
// 			$dbCompany = CCrmCompany::GetListEx(array(), array("ID" => $arResult['ELEMENT']['COMPANY_ID']), false, false, array('TITLE', 'COMPANY_TYPE', 'LOGO'));
// 			if ($arCompany = $dbCompany->Fetch())
// 			{
// 				$arResult['ELEMENT']["COMPANY_TITLE"] = $arCompany['TITLE'];
// 				$arResult['ELEMENT']["COMPANY_TYPE"] = $arCompany["COMPANY_TYPE"];
// 				$arResult['ELEMENT']["COMPANY_LOGO"] = $arCompany["LOGO"];
// 			}
// 		}

// 		$photoD = isset($arResult['ELEMENT']["COMPANY_LOGO"]) ? $arResult['ELEMENT']["COMPANY_LOGO"] : 0;
// 		if($photoD > 0)
// 		{
// 			$listImageInfo = CFile::ResizeImageGet(
// 				$photoD, array('width' => 43, 'height' => 43), BX_RESIZE_IMAGE_PROPORTIONAL );
// 			$arResult['ELEMENT']["COMPANY_LOGO"] = $listImageInfo["src"];
// 		}
// 		$arResult['ELEMENT']["COMPANY_MULTI_FIELDS"] = CCrmMobileHelper::PrepareMultiFieldsData($arResult['ELEMENT']['COMPANY_ID'], CCrmOwnerType::CompanyName);

// 		$arResult['ELEMENT_COMPANY'] = array(
// 			"id" => $arResult['ELEMENT']["COMPANY_ID"],
// 			"name" => $arResult['ELEMENT']["COMPANY_TITLE"],
// 			"image" => $arResult['ELEMENT']["COMPANY_LOGO"],
// 			"entityType" => "company",
// 			"addTitle" => $arResult['COMPANY_TYPE_LIST'][$arResult['ELEMENT']["COMPANY_TYPE"]],
// 			"url" => $companyShowUrl,
// 			"multi" => is_array($arResult['ELEMENT']["COMPANY_MULTI_FIELDS"]) ? $arResult['ELEMENT']["COMPANY_MULTI_FIELDS"] : array()
// 		);
// 	}

// 	$arResult["ON_SELECT_COMPANY_EVENT_NAME"] = "onCrmCompanySelectForDeal_".$arParams['ELEMENT_ID'];
// 	$arResult["ON_DELETE_COMPANY_EVENT_NAME"] = "onCrmCompanyDeleteForDeal_".$arParams['ELEMENT_ID'];

// 	$companyPath = CHTTP::urlAddParams($arParams['COMPANY_SELECTOR_URL_TEMPLATE'], array(
// 		"event" => $arResult["ON_SELECT_COMPANY_EVENT_NAME"]
// 	));

// 	if (!$arParams["RESTRICTED_MODE"] || $arResult['ELEMENT']['COMPANY_ID'])
// 	{
// 		$arResult['FIELDS'][] = array(
// 			'id' => 'COMPANY_ID',
// 			'name' => GetMessage('CRM_FIELD_COMPANY_ID'),
// 			'params' => array('size' => 50),
// 			'type' => 'custom',
// 			'value' => '<div class="mobile-grid-field-select-user">
// 							<div id="mobile-crm-deal-edit-company" data-role="mobile-crm-deal-edit-company">'.
// 							//Company's html is generated on javascript, object BX.Mobile.Crm.ClientEditor
// 							'</div>'. ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\''.CUtil::JSEscape($companyPath).'\')">'.GetMessage("CRM_BUTTON_SELECT").'</a>') .
// 						'</div>'
// 		);
// 	}
// }



// $sProductsHtml = '';
// $componentSettings = array(
// 	'ID' => $arResult['PRODUCT_ROW_EDITOR_ID'],
// 	'FORM_ID' => $arResult['FORM_ID'],
// 	'OWNER_ID' => $arParams['ELEMENT_ID'],
// 	'OWNER_TYPE' => 'D',
// 	'PERMISSION_TYPE' => $isExternal || $arParams['RESTRICTED_MODE'] ? 'READ' : 'WRITE',
// 	'INIT_EDITABLE' => $isExternal ? 'N' : 'Y',
// 	'HIDE_MODE_BUTTON' => 'Y',
// 	'CURRENCY_ID' => $currencyID,
// 	'PERSON_TYPE_ID' => $personTypeId,
// 	'LOCATION_ID' => ($bTaxMode && isset($arResult['ELEMENT']['LOCATION_ID'])) ? $arResult['ELEMENT']['LOCATION_ID'] : '',
// 	'PRODUCT_ROWS' => isset($arResult['PRODUCT_ROWS']) ? $arResult['PRODUCT_ROWS'] : null,
// 	'TOTAL_SUM' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : null,
// 	'TOTAL_TAX' => isset($arResult['ELEMENT']['TAX_VALUE']) ? $arResult['ELEMENT']['TAX_VALUE'] : null,
// 	'PRODUCT_DATA_FIELD_NAME' => $productDataFieldName,
// 	'PATH_TO_PRODUCT_EDIT' => $arParams['PATH_TO_PRODUCT_EDIT'],
// 	'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW'],

// 	"RESTRICTED_MODE" => $arParams["RESTRICTED_MODE"],
// 	"PRODUCT_SELECTOR_URL_TEMPLATE" => $arParams["PRODUCT_SELECTOR_URL_TEMPLATE"],
// 	"ON_PRODUCT_SELECT_EVENT_NAME" => $arResult["ON_PRODUCT_SELECT_EVENT_NAME"]
// );
// if (isset($arParams['ENABLE_DISCOUNT']))
// 	$componentSettings['ENABLE_DISCOUNT'] = ($arParams['ENABLE_DISCOUNT'] === 'Y') ? 'Y' : 'N';
// if (isset($arParams['ENABLE_TAX']))
// 	$componentSettings['ENABLE_TAX'] = ($arParams['ENABLE_TAX'] === 'Y') ? 'Y' : 'N';
// if (is_array($productRowSettings) && count($productRowSettings) > 0)
// {
// 	if (isset($productRowSettings['ENABLE_DISCOUNT']))
// 		$componentSettings['ENABLE_DISCOUNT'] = $productRowSettings['ENABLE_DISCOUNT'] ? 'Y' : 'N';
// 	if (isset($productRowSettings['ENABLE_TAX']))
// 		$componentSettings['ENABLE_TAX'] = $productRowSettings['ENABLE_TAX'] ? 'Y' : 'N';
// }
// ob_start();
// $APPLICATION->IncludeComponent('bitrix:crm.product_row.list',
// 	'mobile',
// 	$componentSettings,
// 	false,
// 	array('HIDE_ICONS' => 'Y')
// );
// $sProductsHtml .= ob_get_contents();
// ob_end_clean();
// unset($componentSettings);

// if (!empty($sProductsHtml))
// {
// 	$arResult['FIELDS'][] = array(
// 		'id' => 'PRODUCT_ROWS',
// 		'name' => GetMessage('CRM_FIELD_PRODUCT_ROWS'),
// 		'type' => 'custom',
// 		'value' => $sProductsHtml
// 	);
// }

//user fields

$fields = [
	[
		'id' => 'NAME',
		'type' => $arResult["IS_EDIT_PERMITTED"] ? 'text' : 'label',
		'name' => 'Название',
		'required' => true,
		'value' => $arResult['ELEMENT']['NAME']
	],
	[
		'id' => 'DETAIL_TEXT',
		'type' => $arResult["IS_EDIT_PERMITTED"] ? 'textarea' : 'label',
		'name' => 'Проведенная работа',
		'required' => true,
		'value' => $arResult['ELEMENT']['DETAIL_TEXT']
	]
];

$arResult["ON_SELECT_COMPANY_EVENT_NAME"] = "onCrmCompanySelectForDeal_" . $arParams['ELEMENT_ID'];
$arResult["ON_DELETE_COMPANY_EVENT_NAME"] = "onCrmCompanyDeleteForDeal_" . $arParams['ELEMENT_ID'];
$arResult["ON_SELECT_CONTACT_EVENT_NAME"] = "onCrmContactSelectForDeal_" . $arParams['ELEMENT_ID'];

// $arParams["RESTRICTED_MODE"] = false;

$props = CIBlock::GetProperties($arParams['IBLOCK_ID'], ['SORT' => 'ASC']);
while ($field = $props->fetch()) {
	$value = '';
	$item = '';
	$items = [];
	$params = [];

	switch ($field['USER_TYPE']) {

		case "Date":
			$type = $arParams["RESTRICTED_MODE"]? 'label':'date';
			$value = $arResult['ELEMENT']['PROPERTY_' . $field['CODE']];
			break;

		case "ECrm":
			$type = false;
			foreach ($field['USER_TYPE_SETTINGS'] as $utsType => $v) {
				if (in_array($utsType, ['COMPANY', 'CONTACT', 'DEAL']) && $v == 'Y') {
					$type = $utsType;
				}
			}
			switch ($type) {
				case "COMPANY":
					$type = 'custom';
					$path = CHTTP::urlAddParams($arParams['COMPANY_SELECTOR_URL_TEMPLATE'], array(
						"event" => $arResult["ON_SELECT_COMPANY_EVENT_NAME"]
					));
					$value = '<div class="mobile-grid-field-select-user">
						<div id="mobile-crm-deal-edit-company" data-role="mobile-crm-deal-edit-company">' .
						//Company's html is generated on javascript, object BX.Mobile.Crm.ClientEditor
						'</div>' . ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\'' . CUtil::JSEscape($path) . '\')">Выбрать</a>') .
						'</div>';

					$company = CCrmCompany::GetByID($arResult['ELEMENT']['PROPERTY_' . $field['CODE']]);
					if ($company) {
						$companyShowUrl = CComponentEngine::MakePathFromTemplate(
							$arParams['COMPANY_SHOW_URL_TEMPLATE'],
							array('company_id' => $company["ID"])
						);
						$arResult['ELEMENT_COMPANY'] = array(
							"id" => $company["ID"],
							"name" => $company["TITLE"],
							"image" => $company["LOGO"],
							"entityType" => "company",
							"url" => $companyShowUrl,
						);
					}
					break;
				case "CONTACT":
					$type = 'custom';
					$contactPath = CHTTP::urlAddParams($arParams['CONTACT_SELECTOR_URL_TEMPLATE'], array(
						"event" => $arResult["ON_SELECT_CONTACT_EVENT_NAME"]
					));
					$value = '<div class="mobile-grid-field-select-user">
 							<div id="mobile-crm-deal-edit-contact" data-role="mobile-crm-deal-edit-contact">' .
						//Contact's html is generated on javascript, object BX.Mobile.Crm.ClientEditor
						'</div>' . ($arParams["RESTRICTED_MODE"] ? '' : '<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageModal(\'' . CUtil::JSEscape($contactPath) . '\')">Выбрать</a>') .
						'</div>';

					$contact = CCrmContact::GetByID($arResult['ELEMENT']['PROPERTY_' . $field['CODE']]);
					if ($contact) {
						$contactShowUrl = CComponentEngine::MakePathFromTemplate(
							$arParams['CONTACT_SHOW_URL_TEMPLATE'],
							array('contact_id' => $contact["ID"])
						);
						$arResult['ELEMENT_CONTACT'] = array(
							"id" => $contact["ID"],
							"name" => $contact["FULL_NAME"],
							"entityType" => "contact",
							"image" => $contact["PHOTO"],
							"addTitle" => $contact['POST'],
							"url" => $contactShowUrl,
						);
					}
					break;
				case "DEAL":

					break;
			}
			break;
		case "employee":
			$type = $arResult["IS_EDIT_PERMITTED"] ? 'select-user' : 'user';
			$value = isset($arResult['ELEMENT']['PROPERTY_' . $field['CODE']]) ? $arResult['ELEMENT']['PROPERTY_' . $field['CODE']] : $USER->GetID();
			$item = CMobileHelper::getUserInfo(isset($arResult['ELEMENT']['PROPERTY_' . $field['CODE']]) ? $arResult['ELEMENT']['PROPERTY_' . $field['CODE']] : $USER->GetID());
			break;

		default:
			if ($field['PROPERTY_TYPE'] == 'L') {
				$type = $arParams["RESTRICTED_MODE"]? 'label':'list';
				$items = [];
				$fieldList = \CIBlockPropertyEnum::GetList(['SORT' => 'ASC'], ["IBLOCK_ID" => $arParams['IBLOCK_ID'], "CODE" => $field['CODE']]);
				while ($fl = $fieldList->Fetch()) {
					$items[$fl['ID']] = $fl['VALUE'];
					if ($arResult['ELEMENT']['PROPERTY_' . $field['CODE']] == $fl['VALUE']) {
						$value = $arParams["RESTRICTED_MODE"]? $fl['VALUE']:$fl['ID'];
					}
				}
			} elseif ($field['PROPERTY_TYPE'] == 'F') {
				$value = $arResult['ELEMENT']['PROPERTY_' . $field['CODE']];
				if ($arParams["RESTRICTED_MODE"]) {
					$type = 'file';
				} else {
					$type = 'custom';
					ob_start();
					$APPLICATION->IncludeComponent(
						"bitrix:main.file.input",
						"mobile",
						array(
							"INPUT_NAME" => 'PROPERTY_' . $field['CODE'],
							"MULTIPLE" => "N",
							"MODULE_ID" => "iblock",
							"MAX_FILE_SIZE" => "100000000",
							"ALLOW_UPLOAD" => "F",
							"ALLOW_UPLOAD_EXT" => ""
						),
						false
					);
					$value = ob_get_contents();
					ob_end_clean();
				}
			} else {
				$type = $arParams["RESTRICTED_MODE"]? 'label':'text';
				if ($field['ROW_COUNT'] > 1) {
					$type = $arParams["RESTRICTED_MODE"]? 'label':'textarea';
				}
				$value = $arResult['ELEMENT']['PROPERTY_' . $field['CODE']];
			}
	}

	if ($field['MULTIPLE'] === 'Y') {
		$params['params'] = ["size" => 5, "multiple" => "multiple"];
	}

	$fields[] = [
		'id' => 'PROPERTY_' . $field['CODE'],
		'type' => $type,
		'name' => $field['NAME'],
		'required' => ($field['IS_REQUIRED'] === 'Y'),
		'items' => $items,
		'item' => $item,
		'params' => $params,
		'value' => $value,
	];
}


$arResult['FIELDS'] = [
	[
		'id' => 'main',
		'fields' => $fields
	]
];
// $arParams["RESTRICTED_MODE"] = false;
$this->IncludeComponentTemplate();
