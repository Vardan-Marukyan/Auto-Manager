<?php

namespace Auto\Manager\Fusion\Crm;

use \Bitrix\Main,
    \Bitrix\Crm\Service;
use Bitrix\Crm\Service\Router;
use Bitrix\Crm\Service\Factory\Dynamic;

Main\Loader::requireModule('crm');

class Container extends Service\Container
{
    public function getFactory(int $entityTypeId): ?Service\Factory
    {
        if ($entityTypeId == $this->getSmartEntityId()) {
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

    public function getRouter(): Router
    {
        return new class($this) extends Router {
            private $container;

            public function __construct($container) {
                parent::__construct();
                $this->container = $container;
            }

            public function parseRequestInSefMode(?\Bitrix\Main\HttpRequest $httpRequest = null): Router\ParseResult
            {
                $result = parent::parseRequestInSefMode($httpRequest);

                if ($result->getComponentName() === 'bitrix:crm.item.kanban')
                {
                    $parameters = $result->getComponentParameters();
                    $entityTypeId = $parameters['ENTITY_TYPE_ID'] ?? $parameters['entityTypeId'] ?? null;

                    if ((int)$entityTypeId === $this->container->getSmartEntityId())
                    {
                        $result = new Router\ParseResult(
                            'auto.manager:crm.item.kanban',
                            $parameters,
                            $result->getTemplateName()
                        );
                    }
                }

                return $result;
            }
        };
    }


    public function getSmartEntityId() : int
    {
        global $DB;
        $sql = "SELECT SMART_PROCESS_ID FROM b_auto_manager_smart_process LIMIT 1";
        $result = $DB->Query($sql);
        if ($row = $result->Fetch()) {
            return $row['SMART_PROCESS_ID'];
        }
        return null;
    }


}

