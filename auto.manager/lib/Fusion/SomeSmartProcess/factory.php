<?php

namespace Auto\Manager\Fusion\SomeSmartProcess;
use \Bitrix\Main,
    \Bitrix\Crm,
    \Bitrix\Crm\Service\Factory\Dynamic;
use Bitrix\Seo\Adv\Auto;


Main\Loader::requireModule('crm');
class Factory extends Dynamic
{
    public function getDeleteOperation(Crm\Item $item, Crm\Service\Context $context = null): Crm\Service\Operation\Delete
    {
        $operation = parent::getDeleteOperation($item, $context);

        return $operation->addAction(
            Crm\Service\Operation::ACTION_AFTER_SAVE,
            new Operation\Action\SendMessage()
        );
    }

    public function getUpdateOperation(Crm\Item $item, Crm\Service\Context $context = null): Crm\Service\Operation\Update
    {
        $operation = parent::getUpdateOperation($item, $context);
        return $operation->addAction(
            Crm\Service\Operation::ACTION_BEFORE_SAVE,
            new Operation\Action\ChangeDeal($item->getEntityTypeId(), 'NEW', 'VIN_CODE', 'REGISTRATION_DATE', ['VIN_CODE', 'LICENSE_PLATE_NUMBER'])
        );
    }

    public function getAddOperation(Crm\Item $item, Crm\Service\Context $context = null): Crm\Service\Operation\Add
    {
        $operation = parent::getAddOperation($item, $context);

        return $operation->addAction(
            Crm\Service\Operation::ACTION_AFTER_SAVE,
            new  Operation\Action\AddDealCheck($item->getEntityTypeId(), 'REGISTRATION_DATE', 'VIN_CODE')
        );

    }

}