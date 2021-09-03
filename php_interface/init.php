<?php


/**
 * Подключение классов
 */
$directory = new \RecursiveDirectoryIterator($_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/classes/');
$iterator = new \RecursiveIteratorIterator($directory);
foreach ($iterator as $entry) {
    if ($entry->getExtension() !== 'php') {
        continue;
    }
    require_once $entry->getRealPath();
}

$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandlerCompatible(
    'tasks',
    'OnTaskUpdate',
    [\Onizamov\Tasks::class, 'onTaskUpdate']
);

/**
 * Агент, который проверяет необходимость запустить отправку получателям по автоматическим отчетам.
 *
 * @return string
 * @throws \Bitrix\Main\ArgumentException
 * @throws \Bitrix\Main\ObjectPropertyException
 * @throws \Bitrix\Main\SystemException
 */
function AutomaticReportEmailSend(): string
{
    \Onizamov\AutomaticReportSend::execute();
    return "AutomaticReportEmailSend();";
}





