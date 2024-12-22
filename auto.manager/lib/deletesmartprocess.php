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
        $smartProcess = TypeTable::getRow(['filter' => ['=CODE' => 'AUTO_MANAGER']]);
        return $smartProcess['ENTITY_TYPE_ID'];
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