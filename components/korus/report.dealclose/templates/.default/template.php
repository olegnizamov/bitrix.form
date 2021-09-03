<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCss("/bitrix/js/report/css/report.min.css");
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
$APPLICATION->AddHeadScript('/bitrix/js/crm/common.js');
?>

<table cellspacing="0" class="reports-list-table">
    <thead>
    <tr>
        <?php foreach ($arResult['HEAD'] as $title => $name) { ?>
            <th class="reports-first-column reports-head-cell-top" style="white-space: nowrap;"> <?= $name ?></th>
        <?php } ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($arResult['DEALS'] as $id => $data) { ?>
        <tr>
        <?php foreach ($data as $key => $val) { ?>
            <?php
            if ($key == 'TITLE') {
                ?><td style="white-space: nowrap;"> <a href="/crm/deal/show/<?= $id ?>/"><?= $val ?></a></td> <?php
                continue;
            }
            ?>
            <td class="reports-first-column" style="white-space: nowrap;"> <?= $val ?></td>
        <?php } ?>
        </tr>
    <?php } ?>
    </tbody>
</table>
<br>
<h3>Среднее время закрытия обращения</h3>
<table cellspacing="0" class="reports-list-table">
    <thead>
    <tr>
        <?php foreach ($arResult['TIME_DIRECTION'] as $direction => $data) { ?>
            <th class="reports-first-column reports-head-cell-top" style="white-space: nowrap; background-color: #F0F0F0;"> <?= $direction ?></th>
        <?php } ?>
    </tr>
    </thead>
    <tbody>
        <tr>
            <?php foreach ($arResult['TIME_DIRECTION'] as $direction => $data) { ?>
                <td class="reports-first-column" style="white-space: nowrap;"> <?= $data['middle'] ?></td>
            <?php } ?>
        </tr>
    </tbody>
</table>

<?php
$this->SetViewTarget("sidebar", 300);
?>

<div class="sidebar-block">
    <div class="sidebar-block-inner">
		<form method="get">
		<div class="filter-block-title report-filter-block-title">Фильтр</div>

        <div class="filter-field filter-field-date-combobox filter-field-date-combobox-month">
				<label for="task-interval-filter" class="filter-field-title">Дата создания сделки</label>

				<span class="filter-date-interval filter-date-interval-after filter-date-interval-before">
					<span class="filter-date-interval-from">
						<input type="text" class="filter-date-interval-from" name="DATE_CREATE_FROM" id="REPORT_INTERVAL_F_DATE_FROM" value="<?= $_GET['DATE_CREATE_FROM'] ?>">
						<a class="filter-date-interval-calendar" href="" title="Выбрать дату в календаре" id="filter-date-interval-calendar-from"><img border="0" src="/bitrix/js/main/core/images/calendar-icon.gif" alt="Выбрать дату в календаре"></a>
					</span>
						<span class="filter-date-interval-hellip">…</span>
					<span class="filter-date-interval-to">
						<input type="text" class="filter-date-interval-to" name="DATE_CREATE_TO" id="REPORT_INTERVAL_F_DATE_TO" value="<?= $_GET['DATE_CREATE_TO'] ?>">
						<a href="" class="filter-date-interval-calendar" title="Выбрать дату в календаре" id="filter-date-interval-calendar-to"><img border="0" src="/bitrix/js/main/core/images/calendar-icon.gif" alt="Выбрать дату в календаре"></a>
					</span>
				</span>


				<script type="text/javascript">

					BX.ready(function() {
						BX.bind(BX("filter-date-interval-calendar-from"), "click", function(e) {
							if (!e) e = window.event;

							var curDate = new Date();
							var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;

							BX.calendar({
								node: this,
								field: BX('REPORT_INTERVAL_F_DATE_FROM'),
								bTime: false
							});

							BX.PreventDefault(e);
						});

						BX.bind(BX("filter-date-interval-calendar-to"), "click", function(e) {
							if (!e) e = window.event;

							var curDate = new Date();
							var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;

							BX.calendar({
								node: this,
								field: BX('REPORT_INTERVAL_F_DATE_TO'),
								bTime: false
							});

							BX.PreventDefault(e);
						});

						jsCalendar.InsertDate = function(value) {
							BX.removeClass(this.field.parentNode.parentNode, "webform-field-textbox-empty");
							var value = this.ValueToString(value);
							this.field.value = value.substr(11, 8) == "00:00:00" ? value.substr(0, 10) : value.substr(0, 16);
							this.Close();
						}

						//OnTaskIntervalChange(BX('task-interval-filter'));
					});

				</script>
			</div>

			<div class="filter-field filter-field-date-combobox filter-field-date-combobox-month">
				<label for="task-interval-filter" class="filter-field-title">Дата начала</label>

				<span class="filter-date-interval filter-date-interval-after filter-date-interval-before">
					<span class="filter-date-interval-from">
						<input type="text" class="filter-date-interval-from" name="BEGINDATE_FROM" id="BEGINDATE_FROM" value="<?= $_GET['BEGINDATE_FROM'] ?>">
						<a class="filter-date-interval-calendar" href="" title="Выбрать дату в календаре" id="filter-begindate-interval-calendar-from"><img border="0" src="/bitrix/js/main/core/images/calendar-icon.gif" alt="Выбрать дату в календаре"></a>
					</span>
						<span class="filter-date-interval-hellip">…</span>
					<span class="filter-date-interval-to">
						<input type="text" class="filter-date-interval-to" name="BEGINDATE_TO" id="BEGINDATE_TO" value="<?= $_GET['BEGINDATE_TO'] ?>">
						<a href="" class="filter-date-interval-calendar" title="Выбрать дату в календаре" id="filter-begindate-interval-calendar-to"><img border="0" src="/bitrix/js/main/core/images/calendar-icon.gif" alt="Выбрать дату в календаре"></a>
					</span>
				</span>


				<script type="text/javascript">

					BX.ready(function() {
						BX.bind(BX("filter-begindate-interval-calendar-from"), "click", function(e) {
							if (!e) e = window.event;

							var curDate = new Date();
							var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;

							BX.calendar({
								node: this,
								field: BX('BEGINDATE_FROM'),
								bTime: false
							});

							BX.PreventDefault(e);
						});

						BX.bind(BX("filter-begindate-interval-calendar-to"), "click", function(e) {
							if (!e) e = window.event;

							var curDate = new Date();
							var curTimestamp = Math.round(curDate / 1000) - curDate.getTimezoneOffset()*60;

							BX.calendar({
								node: this,
								field: BX('BEGINDATE_TO'),
								bTime: false
							});

							BX.PreventDefault(e);
						});

						jsCalendar.InsertDate = function(value) {
							BX.removeClass(this.field.parentNode.parentNode, "webform-field-textbox-empty");
							var value = this.ValueToString(value);
							this.field.value = value.substr(11, 8) == "00:00:00" ? value.substr(0, 10) : value.substr(0, 16);
							this.Close();
						}

						//OnTaskIntervalChange(BX('task-interval-filter'));
					});

				</script>
			</div>

            <div class="filter-field filter-field-date-combobox filter-field-date-combobox-month">
                <label for="task-interval-filter" class="filter-field-title">Время (больше и равно)</label>

                <span class="filter-date-interval filter-date-interval-after filter-date-interval-before">
					<span class="filter-date-interval-from">
						<input type="text" class="filter-date-interval-from" placeholder="Часов"  name="HOUR" id="HOUR" value="<?= $_GET['HOUR'] ?>">
						</span>
						<span class="filter-date-interval-hellip">:</span>
					<span class="filter-date-interval-to">
						<input type="text" class="filter-date-interval-to" placeholder="Минут"   name="MINUTES" id="MINUTES" value="<?= $_GET['MINUTES'] ?>">
						</span>
				</span>
            </div>

			<div class="filter-field filter-field-type chfilter-field-TYPE_ID" callback="RTFilter_chooseBoolean">
				<label for="stages" class="filter-field-title">Направления сделок</label>
				<select id="stages" name="STAGES[]" style="width:100%" caller="true" size="10" multiple>
					<?php foreach($arResult['DIRECTION'] as $directionID => $directionName):?>
							<option value="<?=$directionID?>" <?= in_array($directionID, $_GET['STAGES']) ? 'selected' : '' ?>><?=$directionName?></option>
					<?php endforeach?>
				</select>
			</div>


			<div class="filter-field-buttons">
				<input id="report-rewrite-filter-button" type="submit" value="Применить" class="filter-submit">&nbsp;&nbsp;<input id="report-reset-filter-button" type="submit" name="del_filter_company_search" value="Отмена" class="filter-submit">
			</div>
            <?$url = [
                'DATE_CREATE_FROM' => $_GET['DATE_CREATE_FROM'],
                'DATE_CREATE_TO' => $_GET['DATE_CREATE_TO'],
                'BEGINDATE_FROM' => $_GET['BEGINDATE_TO'],
                'export' => true,
            ];
            foreach ($_GET['STAGES'] as $index => $stage) {
                $url['STAGES'][] = $stage;
            }
            $url = $APPLICATION->GetCurPage() . '?' . http_build_query($url);
            ?>

            <?php if ($arResult['RUN']) { ?>
            <p><a href="<?=$url?>">Скачать в xls</a></p>
            <?php } ?>
	</form>
    </div>
</div>

<?php
$this->EndViewTarget();
?>
<?php
$this->SetViewTarget("pagetitle", 100);
?>
    <a class="webform-small-button webform-small-button-blue webform-small-button-back" href="/crm/reports/report/index.php">
        <span class="webform-small-button-icon"></span>
        <span class="webform-small-button-text">Вернуться к списку отчетов</span>
    </a>
<?php
$this->EndViewTarget();
