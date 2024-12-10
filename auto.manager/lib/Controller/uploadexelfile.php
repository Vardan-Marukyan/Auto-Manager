<?php

namespace Auto\Manager\Controller;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Context;

\Bitrix\Main\Loader::includeModule('crm');

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;

class UploadExelFile extends Controller
{
    public function configureActions()
    {
        return [
            'upload' => [
                'prefilters' => []
            ]
        ];
    }


    public function uploadAction()
    {
        $id = $this->getSmartEntityId();
        $factory = Container::getInstance()->getFactory($id);
        $userFieldId = 'UF_'.$factory->getUserFieldEntityId().'_FIELD_';

        $request = Context::getCurrent()->getRequest();
        $file = $request->getFile('File');

        if(!$this->isExcelFile($file)){
            return $this->createErrorResponse("false");
        }

        $data = $this->parseExcelFile($file['tmp_name']);
        $newData = $this->createDealData($factory, $data, $userFieldId);

        if (!$this->checkRequiredField($newData, [$userFieldId.'BRAND', $userFieldId.'MODEL', $userFieldId.'LICENSE_PLATE_NUMBER', $userFieldId.'VIN_CODE'])){
            return $this->createErrorResponse("Файл не может быть загружен, так как не заполнены обязательные поля.");
        }

        if(!$this->checkUniqueExelData($newData, $userFieldId.'VIN_CODE')){
            return $this->createErrorResponse("Файл не может быть загружен, так как есть совпадающие поля в файле.");
        }

        foreach ($newData as $dataItem){
            if(!$this->checkUnique($factory, $dataItem[$userFieldId.'VIN_CODE'], $userFieldId.'VIN_CODE')){
                return $this->createErrorResponse("Файл не может быть загружен, так как есть совпадающие поля.");
            }
        }

        $changeDateDealsData = $this->addDefaultValue($newData, FormatDate('d.m.Y H:i:s', MakeTimeStamp(date('Y-m-d H:i:s'))), $userFieldId.'REGISTRATION_DATE');

        foreach ($changeDateDealsData as $changeDateDealData){
            $this->createDeal($factory,$changeDateDealData);
        }

        return $this->createSuccessResponse("Сделки были успешно загружены.", $data);
    }

    private function parseExcelFile(string $filePath): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $data = [];
        foreach ($worksheet->getRowIterator() as $row) {
            $rowData = [];
            foreach ($row->getCellIterator() as $cell) {
                $rowData[] = $cell->getValue();
            }
            $data[] = $rowData;
        }

        return $data;
    }

    private function isExcelFile(array $file): bool
    {
        $allowedExtensions = ['xls', 'xlsx'];
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        return in_array(strtolower($extension), $allowedExtensions);
    }

    private function createSuccessResponse(string $summary, array $data): array
    {
        return [
            'STATUS' => 'COMPLETED',
            'SUMMARY' => $summary,
            "DATA" => $data
        ];
    }

    private function createErrorResponse(string $summary): array
    {
        return [
            'STATUS' => 'ERROR',
            'SUMMARY' => $summary,
        ];
    }


    private function createDeal(Factory $factory, array $entityFields) : void
    {

        $items = $factory->getItems();

        $item = $factory->getItemByEntityObject($factory->getDataClass()::createObject());

        foreach ($entityFields as $field => $value) {
            $item->set($field, $value);
        }

        $saveResult = $item->save();
    }

    private function getId($array, $dataValue)
    {

        foreach ($array as $value) {
            AddMessage2Log("Заблокировано не поле: It work!" . $value['VALUE'], "custom_log");
            if ($value['VALUE'] === $dataValue) {
                return $value['ID'];
            }
        }
        return null;
    }

    private function createDealData(Factory $factory, array $data, string $userFieldId) : array
    {
        global $USER;
        $newData = [];
        foreach ($data as $index=>$value){
            $newData[$index]['TITLE'] = $value[0];
            $newData[$index][$userFieldId.'BRAND'] = $value[1];
            $newData[$index][$userFieldId.'MODEL'] = $value[2];
            $newData[$index][$userFieldId.'LICENSE_PLATE_NUMBER'] = $value[3];
            $newData[$index][$userFieldId.'VIN_CODE'] = $value[4];
            $newData[$index][$userFieldId.'YEAR_OF_MANUFACTURE'] = $value[5];
            $newData[$index][$userFieldId.'FUEL_TYPE'] = $this->getId($factory->getUserFieldsInfo()[$userFieldId.'FUEL_TYPE']["ITEMS"], $value[6]);
            $newData[$index][$userFieldId.'CUSTOMS_CLEARED'] = $value[7];
            $newData[$index][$userFieldId.'DATE_OF_CUSTOMS_CLEARANCE'] = date('d.m.Y', strtotime("1900-01-01 +$value[8] days"));
            $newData[$index][$userFieldId.'REGISTRATION_DATE'] = $value[9] !== null ?  date('d.m.Y', strtotime("1900-01-01 +$value[9] days")) : null;
            $newData[$index][$userFieldId.'ID'] = $value[10];
            $newData[$index]['ASSIGNED_BY_ID'] = $USER->GetID();
        }

        return $newData;
    }

    private function checkUnique(Factory $factory, $fieldValue, string $fieldName) : bool
    {
        $items = $factory->getItems();
        foreach ($items as $factoryItem){
            if(
                $factoryItem->getData()[$fieldName] === $fieldValue
                && !empty($factoryItem->getData()[$fieldName])
            ){
                return false;
            }
        }return true;
    }

    private function checkUniqueExelData(array $data, string $fieldName) : bool
    {
        $values = array_column($data, $fieldName);
        $counts = array_count_values($values);
        $duplicates = array_filter($counts, function($count) {
            return $count > 1;
        });

        if (!empty($duplicates)) {
            return false;
        } return true;
    }

    private function addDefaultValue(array $deals, $defaultFalue, string $fieldName):array
    {
        $newData = $deals;
        foreach ($newData as $index => &$deal){
            if(empty($deal[$fieldName])) {
                $deal[$fieldName] = $defaultFalue;
            }
        }
        unset($deal);
        return $newData;

    }

    private function checkRequiredField(array $dealsData, array $fieldsName) : bool
    {
        foreach ($dealsData as $dealData){
            foreach ($fieldsName as $fieldName){
                if(empty($dealData[$fieldName])){
                    return false;
                }
            }
        }return true;
    }

    private function getSmartEntityId() : int
    {
        global $DB;
        $sql = "SELECT SMART_PROCESS_ID FROM b_auto_manager_smart_process LIMIT 1";
        $result = $DB->Query($sql);
        if ($row = $result->Fetch()) {
            return $row['SMART_PROCESS_ID'];
        }
        return 0;
    }
}