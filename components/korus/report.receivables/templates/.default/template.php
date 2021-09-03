
    <?

    use \Bitrix\Main\Grid\Panel\Snippet;

    // $APPLICATION->IncludeComponent(
    //   'bitrix:crm.control_panel',
    //   '',
    //   array(
    //     'ID' => 'REPORTS',
    //     'ACTIVE_ITEM_ID' => 'REPORTS',
    //   ),
    //   $component
    // );
    $snippet = new Snippet();
    
    $APPLICATION->IncludeComponent(
      'bitrix:crm.interface.grid',
      'titleflex',
      array(
        'GRID_ID' => $component::GRID_ID,
        'HEADERS' => $arResult['HEADERS'],
        'FILTER' => $arResult['FILTERS'],
        'SORT' => $arResult['SORT'],
        'SORT_VARS' => $arResult['SORT_VARS'],
        'ROWS' => $arResult["ROWS"],
        'IS_EXTERNAL_FILTER' => false,
        'TOOLBAR_ID' => 'CRMSTORES_TOOLBAR',
        'TOTAL_ROWS_COUNT' => $arResult['PAGINATION']['TOTAL'],
        "EDITABLE"=>true,
        'AJAX_ID' => '',
        'AJAX_OPTION_JUMP' => 'N',
        'AJAX_OPTION_HISTORY' => 'N',
        'AJAX_LOADER' => null,
        'FILTER_PRESETS' => [
          'default' => array(
            'name' => 'Фильтр',
            'fields' => $arResult['FILTERS']
          )
        ],

        'ACTION_PANEL' => array(
          'GROUPS' => array(
            array(
              'ITEMS' => array(
                $snippet->getEditButton(),
                $snippet->getForAllCheckbox(),
              )
            )
          )
        ),


        'PAGINATION' => $arResult['PAGINATION'],

      ),
      $component
    );
    ?>