<?php

CJSCore::Init(["jquery2"]);

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UI\Filter\Type;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Main\UI\Filter\AdditionalDateType;
use Bitrix\Main\UI\Filter\NumberType;

global  $APPLICATION;
/**
 * @var array $arResult
 * @var AutomationReportComponent $component
 */

Extension::load(
    [
        "ui.buttons",
        "ui.fonts.opensans",
        "ui",
        "dnd",
        "loader",
        "date",
    ]
);
?>
<h2>Фильтр</h2>
<div>
    <?php
    $APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
        'FILTER_ID' => $arResult['FILTER_ID'],
        'GRID_ID' => $arResult['FILTER_ID'],
        'FILTER' => $arResult['FILTER_FIELDS'],
        'ENABLE_LIVE_SEARCH' => true,
        'ENABLE_LABEL' => true,
    ]); ?>
</div>
<div style="clear: both;"></div>

<form method="post" action='' enctype="multipart/form-data">
    <hr>
    <h2>Список полей для выгрузки</h2>
    <div class="main-ui-filter-field-container-list">
        <div data-type="LIFT_FIELDS" data-name="LIFT_FIELDS"
             class="main-ui-filter-wield-with-label main-ui-control-field">
            <span title="Регион клиента" class="main-ui-control-field-label">Список полей, которые должны быть в отчете
                <span style="color: rgb(255, 0, 0); vertical-align: super;">*</span>
            </span>
            <div data-name="LIFT_FIELDS" id='div-lift-fields' data-params='{"isMulti":true}'
                 data-items='<?= $arResult['FIELDS'] ?>'
                 data-value='[]' class="main-ui-control main-ui-multi-select">
                <input type="hidden" name="LIFT_FIELDS" id="LIFT_FIELDS" value="" required>
                <span id='main-ui-square-container' class="main-ui-square-container">
                <?php
                foreach ($arResult['DEAL_FIELDS'] as $dealFieldKey => $dealFieldElement) {
                    ?>
                    <span class="main-ui-square"
                          data-item='{"NAME":"<?= $dealFieldElement ?>","VALUE":"<?= $dealFieldKey ?>"}'>
                        <span class="main-ui-square-item"><?= $dealFieldElement; ?></span>
                        <span class="main-ui-item-icon main-ui-square-delete"></span>
                    </span>
                    <?php
                } ?>
                </span>
                <span class="main-ui-square-search">
                    <input type="text" tabindex="2" class="main-ui-square-search-item">
                </span>
                <span class="main-ui-control-value-delete">
                    <div class="main-ui-control-value-delete-item"></div>
                </span>
            </div>
        </div>
        <hr>
        <h2>Настройка данных для выгрузки</h2>

        <div data-type="DAY_OF_WEEK" data-name="DAY_OF_WEEK"
             class="main-ui-filter-wield-with-label main-ui-control-field">
            <span title="День недели" class="main-ui-control-field-label">День недели</span>
            <div data-name="DAY_OF_WEEK" id='div-day-of-week' data-params='{"isMulti":true}'
                 data-items='<?= $arResult['DAY_OF_WEEK'] ?>'
                 data-value='[]' class="main-ui-control main-ui-multi-select">
                <input type="hidden" name="DAY_OF_WEEK" id="DAY_OF_WEEK" value="" required>
                <span id='main-ui-square-container' class="main-ui-square-container">
                <?php
                foreach ($arResult['DAY_OF_WEEK_RESULT'] as $dayOfWeekKey => $dayOfWeekElement) {
                    ?>
                    <span class="main-ui-square"
                          data-item='{"NAME":"<?= $dayOfWeekElement ?>","VALUE":"<?= $dayOfWeekKey ?>"}'>
                        <span class="main-ui-square-item"><?= $dayOfWeekElement; ?></span>
                        <span class="main-ui-item-icon main-ui-square-delete"></span>
                    </span>
                    <?php
                } ?>
                </span>
                <span class="main-ui-square-search">
                    <input type="text" tabindex="4" class="main-ui-square-search-item">
                </span>
                <span class="main-ui-control-value-delete">
                    <div class="main-ui-control-value-delete-item"></div>
                </span>
            </div>
        </div>


        <div data-type="STRING" data-name="RECIPIENTS" class="main-ui-filter-wield-with-label main-ui-control-field">
            <span title="Получатели отчёта" class="main-ui-control-field-label">Получатели отчёта (Заполняются через ;)
                  <span style="color: rgb(255, 0, 0); vertical-align: super;">*</span>
            </span>
            <textarea name="RECIPIENTS" cols="40" rows="5" required class="main-ui-control main-ui-control-textarea"
                      oninvalid="this.setCustomValidity('Пожалуйста, введите получателей отчета')"
                      oninput="setCustomValidity('')"><?= $arResult['VALUES']['RECIPIENTS'] ?></textarea>
        </div>
    </div>
    <button class="ui-btn ui-btn-primary  crm-btn-toolbar-add" type="submit">Отправить</button>
</form>
