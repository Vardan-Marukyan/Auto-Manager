<?php

namespace Auto\Manager\Fusion\SomeSmartProcess\Operation\Action;

use \Bitrix\Main,
    \Bitrix\Crm\Item,
    \Bitrix\Crm\Service\Container,
    \Bitrix\Crm\Service\Operation
    ;

Main\Loader::requireModule('crm');

class AddDealCheck extends Operation\Action
{
    public function __construct(int $factoryEntityId, string $registrationDateFieldName, string $vinCodeFieldName)
    {
            $this->factory = Container::getInstance()->getFactory($factoryEntityId);
            $this->userFieldId = 'UF_'.$this->factory->getUserFieldEntityId().'_FIELD_';
            $this->registrationDateFieldName = $registrationDateFieldName;
    }

    public function process(Item $item): Main\Result
    {
        $result = new Main\Result();

        $unique = $this->checkUnique($item, $this->userFieldId.$this->vinCodeFieldName);
        $validationYear = $this->dataValidationYear($item, $this->userFieldId.$this->registrationDateFieldName);

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

        $this->addDefaultValue($item, FormatDate('d.m.Y H:i:s', MakeTimeStamp(date('Y-m-d H:i:s'))), $this->userFieldId.$this->registrationDateFieldName);
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

    public function addDefaultValue(Item $item, $value, string $fieldName)
    {
        if(empty($item->getData()[$fieldName])){
            $data = $item->set($fieldName, $value);
            $data->save();
        }
    }

}