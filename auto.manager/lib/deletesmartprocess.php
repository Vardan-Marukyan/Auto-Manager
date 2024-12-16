<?php

namespace Auto\Manager;
use \Bitrix\Crm\Service;
use Bitrix\Crm\Model\Dynamic\TypeTable;

class DeleteSmartProcess
{
    public function __construct()
    {
        $this->smartProcessEntityId = $this->getSmartProcessEntityId();
        $this->deleteSmartProccesItems();
        $this->deleteSmartProcess();
    }

    public function getSmartProcessEntityId()
    {
        global $DB, $APPLICATION;
        $sql = "SELECT SMART_PROCESS_ID FROM b_auto_manager_smart_process LIMIT 1";
        $result = $DB->Query($sql);
        if ($row = $result->Fetch()) {
            return $this->smartProcessEntityId = $row['SMART_PROCESS_ID'];
        }return null;
    }

    public function deleteSmartProcess()
    {
        $type = TypeTable::getByEntityTypeId($this->smartProcessEntityId)->fetchObject();
        $deleteResult = $type->delete();

        if (!$deleteResult->isSuccess()){
            return true;
        }return false;
    }

    public function  deleteSmartProccesItems()
    {
        $factory = Service\Container::getInstance()->getFactory($this->smartProcessEntityId);
        $items = $factory->getItems();

        if(empty($items)){
            return;
        }

        foreach ($items as $item){
            $deleteOperation = $factory->getDeleteOperation($item);
            $operationResult = $deleteOperation->launch();
            if (!$operationResult->isSuccess()){
                return;
            }
        }
    }
}