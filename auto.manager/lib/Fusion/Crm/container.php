<?php

namespace Auto\Manager\Fusion\Crm;

use \Bitrix\Main,
    \Bitrix\Crm\Service;

Main\Loader::requireModule('crm');

class Container extends Service\Container
{
    public function getFactory(int $entityTypeId): ?Service\Factory
    {
        if ($entityTypeId) {
            $identifier = static::getIdentifierByClassName(static::$dynamicFactoriesClassName, [$entityTypeId]);
            if (Main\DI\ServiceLocator::getInstance()->has($identifier)) {
                return Main\DI\ServiceLocator::getInstance()->get($identifier);
            }

            $type = $this->getTypeByEntityTypeId($entityTypeId);
            if (!$type) {
                return null;
            }


            $factory = new \Auto\Manager\Fusion\SomeSmartProcess\Factory($type);
            Main\DI\ServiceLocator::getInstance()->addInstance(
                $identifier,
                $factory
            );
            return $factory;
        }
        return parent::getFactory($entityTypeId);
    }
}

