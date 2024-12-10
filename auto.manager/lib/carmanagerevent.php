<?php

namespace Auto\Manager;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
\Bitrix\Main\UI\Extension::load("ui.stepprocessing");


class CarManagerEvent
{

    public static function onPageStartAutoManagerEvent()
    {
        if(file_exists($_SERVER["DOCUMENT_ROOT"].'/local/modules/auto.manager/vendor/autoload.php')){
            require_once($_SERVER["DOCUMENT_ROOT"].'/local/modules/auto.manager/vendor/autoload.php');
        }

        ServiceLocator::getInstance()->addInstanceLazy(
            'crm.service.container',
            [
                'className' => '\\Auto\\Manager\\Fusion\\Crm\\Container'
            ]
        );
    }

}