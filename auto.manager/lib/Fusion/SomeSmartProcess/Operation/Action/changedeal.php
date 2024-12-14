<?php

namespace Auto\Manager\Fusion\SomeSmartProcess\Operation\Action;

use \Bitrix\Main,
    \Bitrix\Crm\Item,
    \Bitrix\Crm\Service\Container,
    \Bitrix\Crm\Service\Operation
    ;

Main\Loader::requireModule('crm');

class ChangeDeal extends Operation\Action
{

    public function __construct(int $factoryEntityId, string $stageStatus, string $vinCodeFieldName, string $registrationDateFieldName, array $blockFieldsNames)
    {
        $this->factory = Container::getInstance()->getFactory($factoryEntityId);;
        $this->stageStatus = $stageStatus;
        $categoryId = $this->factory->getDefaultCategory()->getId();

        $this->userFieldId = 'UF_'.$this->factory->getUserFieldEntityId().'_FIELD_';
        $this->registrationDateFieldName = $registrationDateFieldName;
        $this->vinCodeFieldName = $vinCodeFieldName;
        $this->categoryId = 'DT'.$factoryEntityId.'_'.$categoryId.':';
        $this->blockFieldsNames = $blockFieldsNames;

    }


    public function process(Item $item): Main\Result
    {
        $result = new Main\Result();
        $unique = $this->checkUnique($item, $this->userFieldId.$this->vinCodeFieldName);
        $validationYear = $this->dataValidationYear($item, $this->userFieldId.$this->registrationDateFieldName);


        foreach ($this->blockFieldsNames as $fieldName){
            $blockFieldResult = $this->blockField($item, $this->categoryId.'IN_OPERATION', $fieldName);

            if (!$blockFieldResult){
                $result->addError(
                    new Main\Error('Изменить поля нельзя: '.$this->factory->getFieldsCollection()->getField($this->userFieldId.$fieldName)->getTitle())
                );
            }
        }

        if (!$unique){
            $result->addError(
                new Main\Error('Машина с таким вин кодом уже существует')
            );
        }

        if (!$validationYear){
            $result->addError(
                new Main\Error('Неправильно указана дата')
            );
        }

        $fuel = $this->hideField($item, 'b_auto_manager_smart_process_hide_field_fuel_type', $this->categoryId.'NEW', $this->userFieldId.'FUEL_TYPE', $this->userFieldId.'VIN_CODE', 'VIN', 'FUEL_TYPE');
        $year = $this->hideField($item, 'b_auto_manager_smart_process_hide_field_year', $this->categoryId.'NEW', $this->userFieldId.'YEAR_OF_MANUFACTURE', $this->userFieldId.'VIN_CODE', 'VIN', 'YEAR_OF_MANUFACTURE');

        return $result;
    }

    public function checkUnique(Item $item, string $fieldName ) : bool
    {
        $items = $this->factory->getItems();
        foreach ($items as $factoryItem){
            if (
                !empty($item->getData()[$fieldName])
                && $factoryItem->getData()[$fieldName] === $item->getData()[$fieldName]
                && $factoryItem->getId() != $item->getId()
            ){
                return false;
            }
        }return true;
    }

    public function dataValidationYear(Item $item, string $fieldName) : bool
    {
        $firstAutoYear = 1900;
        $year = date('Y');
        $fieldsYear = date('Y', strtotime($item->getData()[$fieldName]));
        if(
            $fieldsYear <= $year
            && $fieldsYear > $firstAutoYear
        ){
            return true;
        }return false;
    }

    public function blockField(Item $item, string $stageId ,string $fieldName) : bool
    {
        $oldItemData = $this->factory->getItem($item->getId())->getData()[$this->userFieldId.$fieldName];
        if($item->getStageId() == $stageId){
            if ($oldItemData != $item->getData()[$this->userFieldId.$fieldName]){
                return false;
            }return true;
        }return true;
    }

    public function hideField(Item $item, string $tableName, string $stageId, string $fieldName, string $keyFieldName, string $keyNoteName,  $hideNoteNmae )
    {
        global $DB;
        $key = $item->getData()[$keyFieldName];
        if (
            $item->isChangedStageId()
            && $item->getStageId() !== $stageId
        ) {
            $fieldValue = $item->getData()[$fieldName];
            if (empty($fieldValue)) {
                return;
            }

            $this->addNoteBd($DB, $tableName, $key, $fieldValue, $keyNoteName, $hideNoteNmae);

            $data = $item->set($fieldName, null);
            $data->save();
        }

        if ($item->getStageId() === $stageId){
            $value = $this->getNoteBd($DB, $tableName, $key, $hideNoteNmae, $keyNoteName);
            if (empty($value)){
                return;
            }
            $data = $item->set($fieldName, $value);
            $data->save();

            $this->deleteNoteBd($DB, $tableName, $key, $keyNoteName);
        }
    }

    public function addNoteBd($DB, string $tableName, string $key,  string $fieldValue, string $keyNoteName, $hideNoteNmae)
    {
        $sql = "INSERT INTO $tableName ($keyNoteName, $hideNoteNmae) VALUES ('" . $key . "', " . $fieldValue . ")";
        $DB->Query($sql);
    }

    public function getNoteBd($DB, string $tableName, string $key, string $noteCellName, string $keyNoteName)
    {
        $sql = "SELECT $noteCellName FROM $tableName WHERE $keyNoteName = '".$key."'";
        $result = $DB->Query($sql);
        if ($row = $result->Fetch()) {
            return $row[$noteCellName];
        }return null;
    }

    public function deleteNoteBd($DB, string $tableName, string $key, string $keyNoteName) : void
    {
        $sql = "DELETE FROM $tableName WHERE $keyNoteName = '".$key."'";
        $DB->Query($sql);
    }

}