<?php

namespace Auto\Manager\Fusion\SomeSmartProcess\Operation\Action;

use \Bitrix\Main,
    \Bitrix\Crm\Item,
    \Bitrix\Crm\Service\Operation
    ;

Main\Loader::requireModule('crm');

class SendMessage extends Operation\Action
{
    public function process(Item $item): Main\Result
    {
        $arMessageFields = [
            "TO_USER_ID"     => $item->getCreatedBy(),
            "FROM_USER_ID"   => 0,
            "NOTIFY_TYPE"    => \IM_NOTIFY_SYSTEM,
            "NOTIFY_MODULE"  => "tasks",
            "NOTIFY_MESSAGE" => 'Запись была удален'.PHP_EOL.'Имя сделки: '.$item->getTitle(),
        ];

        \CIMNotify::Add($arMessageFields);

        return new Main\Result();
    }
}