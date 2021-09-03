<?php

CJSCore::Init(array("jquery", "date"));
?>
<?php
if($arResult['ERROR']){
    print '<p class="crm-entity-widget-content-error-text ">'.$arResult['ERROR'].'</p>';
}

if(!$arResult['ID']):
?>
<div class="crm-entity-widget-content-block-field-half-width">
    <form method="post" class="crm-entity-widget-content" enctype="multipart/form-data">
    <input type="hidden" name="sessid" value="<?=bitrix_sessid()?>">

            <?php if($arResult['CAN_CHANGE_FROM']):?>
            <div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">

                <div class="crm-entity-widget-content-block-title">
                    <span class="crm-entity-widget-content-block-title-text">От кого
                        <span>*</span>
                    </span>
                </div>
                <div class="crm-entity-widget-content-block-inner">
                    <div class="crm-entity-widget-content-block-field-container">
                    
                                <div class="crm-entity-widget-content-select">
                                    <select name="FROM" class="crm-entity-widget-content-select-hidden">
                                        <? foreach ($arResult['USERS'] as $optionKey => $optionValue) : ?>
                                            <option value="<?= $optionKey ?>" <?= $optionKey == $arResult['FIELDS']['FROM']? 'selected':'' ?>><?= $optionValue ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                    </div>
                </div>
            </div>
            <?php endif;?>


            <div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">

                <div class="crm-entity-widget-content-block-title">
                    <span class="crm-entity-widget-content-block-title-text">Заместитель
                        <span>*</span>
                    </span>
                </div>
                <div class="crm-entity-widget-content-block-inner">
                    <div class="crm-entity-widget-content-block-field-container">
                    
                                <div class="crm-entity-widget-content-select">
                                    <select name="DEPUT" class="crm-entity-widget-content-select-hidden ">
                                        <option value="">-</option>
                                        <? foreach ($arResult['USERS'] as $optionKey => $optionValue) : ?>
                                            <option value="<?= $optionKey ?>" <?= $optionKey == $arResult['FIELDS']['DEPUT']? 'selected':'' ?>><?= $optionValue ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                    </div>
                </div>
            </div>

            <div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">

            <div class="crm-entity-widget-content-block-title">
                <span class="crm-entity-widget-content-block-title-text">Тип отсутствия
                    <span>*</span>
                </span>
            </div>
            <div class="crm-entity-widget-content-block-inner">
                <div class="crm-entity-widget-content-block-field-container">
                    <div class="crm-entity-widget-content-select">
                        <select name="TYPE" class="crm-entity-widget-content-select-hidden">
                                <option value="211" <?= 211 == $arResult['FIELDS']['TYPE']? 'selected':'' ?>>Отпуск</option>
                                <option value="212" <?= 212 == $arResult['FIELDS']['TYPE']? 'selected':'' ?>>Командировка</option>
                                <option value="213" <?= 213 == $arResult['FIELDS']['TYPE']? 'selected':'' ?>>Больничный</option>
                        </select>
                    </div>
                </div>
            </div>
            </div>

            <div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-date">

                <div class="crm-entity-widget-content-block-title">
                    <span class="crm-entity-widget-content-block-title-text">Период
                        <span> *</span>
                    </span>
                </div>
                <div class="crm-entity-widget-content-block-inner">
                    <div class="crm-entity-widget-content-block-field-container">
                                <div class="crm-entity-widget-content-block-inner crm-entity-widget-content-block-field-half-width">
                                    <input name="PERIOD_FROM" onclick="BX.calendar({node: this, field: this, bTime: false});" class="crm-entity-widget-content-input" type="text" value="<?= $arResult['FIELDS']['PERIOD_FROM'] ?>">
                                </div>
                    <div>&nbsp; - &nbsp;</div>
                                <div class="crm-entity-widget-content-block-inner crm-entity-widget-content-block-field-half-width">
                                    <input name="PERIOD_TO" onclick="BX.calendar({node: this, field: this, bTime: false});" class="crm-entity-widget-content-input" type="text" value="<?= $arResult['FIELDS']['PERIOD_TO'] ?>">
                                </div>
                    
                    </div>
                </div>
            </div>



            <div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">

                <div class="crm-entity-widget-content-block-title">
                    <span class="crm-entity-widget-content-block-title-text">Комментарий
                        <span>*</span>
                    </span>
                </div>
                <div class="crm-entity-widget-content-block-inner">
                    <div class="crm-entity-widget-content-block-field-container">  
                        <textarea name="COMMENT" class="crm-entity-widget-content-textarea" rows="6"><?= $arResult['FIELDS']['COMMENT'] ?></textarea>   
                    </div>
                </div>
            </div>



        <input type="submit" value="Добавить отсутствие" class="ui-btn ui-btn-success">
    </form>
</div>

                                        <?php else:?>
<p>Отсутствие было успешно зарегистрировано</p>
                                        <?php endif;?>