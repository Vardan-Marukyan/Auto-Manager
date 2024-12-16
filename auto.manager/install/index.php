<?php
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Auto\Manager\CreateSmartProcess;
use Auto\Manager\DeleteSmartProcess;
Loader::includeModule('crm');
Loc::loadMessages(__FILE__);

class auto_manager extends CModule
{

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

    public function InstallFiles() {
        if (is_dir($p = $_SERVER["DOCUMENT_ROOT"]."/local/modules/".$this->MODULE_ID."/install/components")){
            if ($dir = opendir($p)){
                while (false !== $item = readdir($dir)){
                    if ($item == ".." || $item == ".")
                        continue;
                    CopyDirFiles($p."/" .$item, $_SERVER["DOCUMENT_ROOT"]."/local/components/".$this->MODULE_ID."/".$item, $ReWrite = True, $Recursive = True);
                }
                closedir($dir);
            }
        }

        $composerPath = $_SERVER['DOCUMENT_ROOT'] . "/local/modules/".$this->MODULE_ID."/composer.json";
        $modulePath = $_SERVER['DOCUMENT_ROOT'] . "/local/modules/".$this->MODULE_ID."/";

        if (file_exists($composerPath)) {
            exec("cd $modulePath && composer install", $output, $returnVar);
        }
    }

    public function UnInstallFiles() {
        if (is_dir($componentsCustom = $_SERVER["DOCUMENT_ROOT"]."/local/components/".$this->MODULE_ID))
        {
            DeleteDirFilesEx(str_replace($_SERVER["DOCUMENT_ROOT"], "", $componentsCustom));
        }

        if (is_dir($vendorPath = $_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . $this->MODULE_ID . "/vendor")) {
            DeleteDirFilesEx(str_replace($_SERVER["DOCUMENT_ROOT"], "", $vendorPath));
        }

        if (file_exists($lockFile = $_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . $this->MODULE_ID . "/composer.lock"))
        {
            unlink($lockFile);
        }
    }

    public function InstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandler(
            'main',
            'OnPageStart',
            $this->MODULE_ID,
            'Auto\\Manager\\CarManagerEvent',
            'onPageStartAutoManagerEvent'
        );
        return true;
    }

    public function UnInstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'main',
            'OnPageStart',
            $this->MODULE_ID,
            'Auto\\Manager\\CarManagerEvent',
            'onPageStartAutoManagerEvent'
        );
        return true;
    }

    public  function InstallDB()
    {   global $DB, $APPLICATION;
        $errors = false;
        $errors = $DB->RunSQLBatch(__DIR__."/db/".strtolower($DB->type)."/install.sql");
        if (!empty($errors)){
            $APPLICATION->ThrowException(implode("", $errors));
            return false;
        }

        return true;
    }

    public  function UnInstallDB()
    {   global $DB, $APPLICATION;

        $errors = false;
        $errors = $DB->RunSQLBatch(__DIR__."/db/".strtolower($DB->type)."/uninstall.sql");
        if (!empty($errors)){
            $APPLICATION->ThrowException(implode("", $errors));
            return false;
        }

        return true;
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        if (Loader::includeModule($this->MODULE_ID))
        {
            $this->InstallDB();
            $this->InstallFiles();
            new CreateSmartProcess();
            $this->InstallEvents();
        }
    }


    public function DoUninstall()
    {
        $this->UnInstallFiles();
        new DeleteSmartProcess();
        $this->UnInstallEvents();
        $this->UnInstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}
