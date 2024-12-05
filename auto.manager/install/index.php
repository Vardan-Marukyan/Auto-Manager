<?php
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use \Bitrix\Crm\Service;
use Auto\Manager\CategoryCreator;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Main\DI\ServiceLocator;
Loader::includeModule('crm');
Loc::loadMessages(__FILE__);



class auto_manager extends CModule
{
    private int $smartProcessEntityId;

    public function __construct()
    {
        $arModuleVersion = [];

        include(__DIR__ . "/version.php");

        $this->MODULE_ID = "auto.manager";
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("AUTO_MANAGER_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("AUTO_MANAGER_MODULE_DESCRIPTION");
        $this->MODULE_GROUP_RIGHTS = "Y";
        $this->MODULE_SORT = 1;


    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        if (Loader::includeModule($this->MODULE_ID))
        {
            $this->installBD();
            $this->createSmartProcess();
            $this->creatFiled();
            new CategoryCreator($this->smartProcessEntityId);
        }
    }

    public function DoUninstall()
    {
        $this->getItem();
        $this->deleteSmartProccesItems();
        $this->deleteSmartProcess();
        $this->unInstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }


    public function createSmartProcess()
    {
        global $DB;
        $container = Service\Container::getInstance();

        $typeDataClass = $container->getDynamicTypeDataClass();

        $type = $typeDataClass::createObject();

        $type->set("TABLE_NAME", "AUTO_MANAGER")
            ->set("NAME", "AUTO_MANAGER")
            ->set('TITLE', 'Управления данными автомобилей')
            ->set('CODE', 'AUTO_MANAGER')
            ->set('IS_STAGES_ENABLED', 'Y')
            ->set('IS_USE_IN_USERFIELD_ENABLED', 'Y')
        ;


        $result = $type->save();
        if($result->isSuccess()){
            $this->smartProcessEntityId = $type->getEntityTypeId();
            $dataSql = file_get_contents(__DIR__ . '/db/mysql/data.sql');
            $dataSql = str_replace(
                '#SMART_PROCESS_ID#',
                $DB->ForSql($type->getEntityTypeId()),
                $dataSql
            );

            $DB->Query($dataSql);
        }else{
            return;
        }
    }

    public function getItem()
    {
        global $DB;
        $sql = "SELECT SMART_PROCESS_ID FROM b_auto_manager_smart_process LIMIT 1";
        $result = $DB->Query($sql);
        if ($row = $result->Fetch()) {
            $this->smartProcessEntityId = $row['SMART_PROCESS_ID'];
        }
    }

    public function deleteSmartProcess()
    {
        $type = TypeTable::getByEntityTypeId($this->smartProcessEntityId)->fetchObject();
        $deleteResult = $type->delete();

        if (!$deleteResult->isSuccess()){
            return;
        }
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

    public function installBD()
    {
        $sql = file_get_contents(__DIR__."/db/mysql/install.sql");
        if($sql){
            Bitrix\Main\Application::getConnection()->query($sql);
        }
    }

    public function unInstallDB()
    {
        $sql = file_get_contents(__DIR__."/db/mysql/uninstall.sql");
        if($sql){
            Bitrix\Main\Application::getConnection()->query($sql);
        }
    }

    public function creatFiled()
    {
        $factory = Service\Container::getInstance()->getFactory($this->smartProcessEntityId);
        $fieldEntityId = $factory->getUserFieldEntityId();
        $fieldData =  $this->createUserField($fieldEntityId);
        $result = new \CUserTypeEntity();


        foreach ($fieldData as $fieldItem){
            $fieldId  = $result->Add($fieldItem);

            if ($fieldItem['USER_TYPE_ID'] == 'enumeration'){
                $enum = new \CUserFieldEnum();
                $enum->SetEnumValues($fieldId, [
                    'n0' => ['VALUE' => 'бензин', 'SORT' => 100, 'DEF' => 'N'],
                    'n1' => ['VALUE' => 'дизель', 'SORT' => 200, 'DEF' => 'N'],
                    'n2' => ['VALUE' => 'гибрид', 'SORT' => 300, 'DEF' => 'N'],
                    'n3' => ['VALUE' => 'электричество', 'SORT' => 400, 'DEF' => 'N'],
                ]);
            }
        }
    }

    public function createUserField($entityId)
    {
        return[
            [
                'ENTITY_ID' => $entityId,
                'FIELD_NAME' => 'UF_'.$entityId.'_FIELD_BRAND',
                'USER_TYPE_ID' => 'string',
                'SORT' => 10,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'SETTINGS' => [
                    'DEFAULT_VALUE' => '',
                ],
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Марка',
                    'en' => 'Brand',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Марка',
                    'en' => 'Brand',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Марка',
                    'en' => 'Brand',
                ],

            ],

            [
                'ENTITY_ID' => $entityId,
                'FIELD_NAME' => 'UF_'.$entityId.'_FIELD_MODEL',
                'USER_TYPE_ID' => 'string',
                'SORT' => 20,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'SETTINGS' => [
                    'DEFAULT_VALUE' => '',
                ],
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Модель',
                    'en' => 'Model',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Модель',
                    'en' => 'Model',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Модель',
                    'en' => 'Model',
                ],
            ],

            [
                'ENTITY_ID' => $entityId,
                'FIELD_NAME' => 'UF_'.$entityId.'_FIELD_LICENSE_PLATE_NUMBER',
                'USER_TYPE_ID' => 'string',
                'SORT' => 30,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'Y',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'SETTINGS' => [
                    'DEFAULT_VALUE' => '',
                ],
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Номерной знак',
                    'en' => 'License Plate Number',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Номерной знак',
                    'en' => 'License Plate Number',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Номерной знак',
                    'en' => 'License Plate Number',
                ],

            ],

            [
                'ENTITY_ID' => $entityId,
                'FIELD_NAME' => 'UF_'.$entityId.'_FIELD_VIN_CODE',
                'USER_TYPE_ID' => 'string',
                'SORT' => 40,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'SETTINGS' => [
                    'DEFAULT_VALUE' => '',
                ],
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Вин код',
                    'en' => 'VIN code',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Вин код',
                    'en' => 'VIN code',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Вин код',
                    'en' => 'VIN code',
                ],

            ],

            [
                'ENTITY_ID' => $entityId,
                'FIELD_NAME' => 'UF_'.$entityId.'_FIELD_YEAR_OF_MANUFACTURE',
                'USER_TYPE_ID' => 'double',
                'SORT' => 50,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'SETTINGS' => [
                    'DEFAULT_VALUE' => '',
                ],
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Год выпуска',
                    'en' => 'Year of Manufacture',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Год выпуска',
                    'en' => 'Year of Manufacture',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Год выпуска',
                    'en' => 'Year of Manufacture',
                ],

            ],

            [
                'ENTITY_ID' => $entityId,
                'FIELD_NAME' => 'UF_'.$entityId.'_FIELD_FUEL_TYPE',
                'USER_TYPE_ID' => 'enumeration',
                'SORT' => 60,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'SETTINGS' => [
                    'DEFAULT_VALUE' => '',
                    'LIST' => [
                        ['VALUE' => 'бензин', 'DEF' => false, 'SORT' => 100],
                        ['VALUE' => 'дизель', 'DEF' => false, 'SORT' => 200],
                        ['VALUE' => 'гибрид', 'DEF' => false, 'SORT' => 300],
                        ['VALUE' => 'электричество', 'DEF' => false, 'SORT' => 400],
                    ]
                ],
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Тип топлива',
                    'en' => 'Fuel Type',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Тип топлива',
                    'en' => 'Fuel Type',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Тип топлива',
                    'en' => 'Fuel Type',
                ],

            ],

            [
                'ENTITY_ID' => $entityId,
                'FIELD_NAME' => 'UF_'.$entityId.'_FIELD_CUSTOMS_CLEARED',
                'USER_TYPE_ID' => 'boolean',
                'SORT' => 70,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'SETTINGS' => [
                    'DEFAULT_VALUE' => 'N',
                ],
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Растаможен',
                    'en' => 'Customs Cleared',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Растаможен',
                    'en' => 'Customs Cleared',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Растаможен',
                    'en' => 'Customs Cleared',
                ],

            ],

            [
                'ENTITY_ID' => $entityId,
                'FIELD_NAME' => 'UF_'.$entityId.'_FIELD_DATE_OF_CUSTOMS_CLEARANCE',
                'USER_TYPE_ID' => 'datetime',
                'SORT' => 80,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'SETTINGS' => [
                    'DEFAULT_VALUE' => '',
                ],
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Дата растаможки',
                    'en' => 'Date of Customs Clearance',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Дата растаможки',
                    'en' => 'Date of Customs Clearance',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Дата растаможки',
                    'en' => 'Date of Customs Clearance',
                ],
            ],

            [
                'ENTITY_ID' => $entityId,
                'FIELD_NAME' => 'UF_'.$entityId.'_FIELD_REGISTRATION_DATE',
                'USER_TYPE_ID' => 'datetime',
                'SORT' => 90,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'SETTINGS' => [
                    'DEFAULT_VALUE' => '',
                ],
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Дата регистрации',
                    'en' => 'Registration date',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Дата регистрации',
                    'en' => 'Registration date',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Дата регистрации',
                    'en' => 'Registration date',
                ],
            ],

            [
                'ENTITY_ID' => $entityId,
                'FIELD_NAME' => 'UF_'.$entityId.'_FIELD_ID',
                'USER_TYPE_ID' => 'double',
                'SORT' => 100,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'Y',
                'SETTINGS' => [
                    'DEFAULT_VALUE' => '',
                ],
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Идентификатор аккаунта автомобиля',
                    'en' => 'Account ID of the Car',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Идентификатор аккаунта автомобиля',
                    'en' => 'Account ID of the Car',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Идентификатор аккаунта автомобиля',
                    'en' => 'Account ID of the Car',
                ],
            ],
        ];
    }


}
