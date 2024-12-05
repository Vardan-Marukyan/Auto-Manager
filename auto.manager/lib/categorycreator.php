<?php

namespace Auto\Manager;

use Bitrix\Main\Loader;
use Bitrix\Crm\StatusTable;
use Bitrix\Crm\Service\Container;

Loader::includeModule('crm');

class CategoryCreator
{
    public int $carManagementCategoryID;
    public int $smartProcessEntityID;
    public string $stageStatusEntityId;
    public string $stageStatusId;
    public $factory;

    public function __construct(int $smartProcessEntityID)
    {
        if (empty($smartProcessEntityID)) {
            throw new \InvalidArgumentException('ID смарт-процесса не может быть пустым.');
        }

        $this->factory = Container::getInstance()->getFactory($smartProcessEntityID);
        $this->smartProcessEntityID = $smartProcessEntityID;
        if (!$this->factory) {
            throw new \RuntimeException('Не удалось получить фабрику для указанного ID смарт-процесса.');
        }


        $this->createCarManagementCategory($factory);
        $this->doCategoryDefault($factory);


        $this->stageStatusEntityId = 'DYNAMIC_'.$this->smartProcessEntityID.'_STAGE_'.$this->carManagementCategoryID;
        $this->stageStatusId = 'DT'.$this->smartProcessEntityID.'_'.$this->carManagementCategoryID.':';
        $this->deleteDefaultStage();
        $this->addStage();
        $this->updateStage($this->stageStatusId."NEW", "NAME", "Новый");
    }

    public function createCarManagementCategory()
    {
        $categoryName = "Варонка автомобилей";
        $newCategoryData = [
            'NAME' => $categoryName,
            'SORT' => 10,
        ];

        $result = $this->factory->createCategory($newCategoryData);
        $resultSave = $result->save();

        if ($resultSave->isSuccess()) {
            $this->carManagementCategoryID = $result->getId();
        } else {
            throw new \RuntimeException('Ошибка при сохранении категории: ' . implode(', ', $resultSave->getErrorMessages()));
        }
    }

    public function doCategoryDefault() : void
    {
        $defaultCategory = $this->factory->getDefaultCategory();

        if($defaultCategory){
            $defaultCategoryResult = $defaultCategory->setIsDefault(false);
            $defaultCategoryResult->save();
        }

        $ourCategory = $this->factory->getCategory($this->carManagementCategoryID);
        if ($ourCategory){
            $ourCategoryResult = $ourCategory->setIsDefault(true);
            $ourCategoryResult->save();
        }
    }

    public function updateStage(string $stageStatusId, string $fieldName, string $newStageName) : void
    {
        $status = StatusTable::getList([
            'filter' => ['STATUS_ID' => $stageStatusId]
        ])->fetch();

        if($status){
            $result = StatusTable::update($status['ID'], [
                $fieldName => $newStageName
            ]);

            if (!$result->isSuccess()) {
                throw new \RuntimeException("Статус не обновлен:" . implode(', ', $result->getErrorMessages()) . "<br>");
            }
        }else {
            throw new \RuntimeException("Статус с ID {$stageStatusId} не найден.");
        }
    }

    public function deleteDefaultStage() : void
    {
        $statusList = StatusTable::getList([
            'filter' => [
                'ENTITY_ID' => $this->stageStatusEntityId,
                'SYSTEM' => 'N'
            ],
        ]);

        while ($status = $statusList->fetch()) {
            $statusId = $status['ID'];

            $deleteResult = StatusTable::delete($statusId);

            if (!$deleteResult->isSuccess()) {
                throw new \RuntimeException("Ошибка при удалении статуса с ID {$statusId}: " . implode(', ', $deleteResult->getErrorMessages()) . "<br>");
            }
        }
    }

    public function addStage() : void
    {
        $categoryId = $this->carManagementCategoryID;
        $statusEntityId = $this->stageStatusEntityId;
        $statusId = $this->stageStatusId;

        $dataStages = [
            [
                'ENTITY_ID' => $statusEntityId,
                'STATUS_ID' => $statusId.'IN_OPERATION',
                'NAME' => 'В эксплуатации',
                'SORT' => 20,
                'COLOR' => '#2ea8b6',
                'SEMANTIC_ID' => 'PROCESSING',
                'CATEGORY_ID' => $categoryId,
            ],
            [
                'ENTITY_ID' => $statusEntityId,
                'STATUS_ID' => $statusId.'UNDER_REPAIR',
                'NAME' => 'В ремонте',
                'SORT' => 30,
                'COLOR' => '#0f9bab',
                'SEMANTIC_ID' => 'PROCESSING',
                'CATEGORY_ID' => $categoryId,
            ],
            [
                'ENTITY_ID' => $statusEntityId,
                'STATUS_ID' => $statusId.'SOLD',
                'NAME' => 'Продан',
                'SORT' => 40,
                'COLOR' => '#0d7581',
                'SEMANTIC_ID' => 'PROCESSING',
                'CATEGORY_ID' => $categoryId,
            ]
        ];

        foreach ($dataStages as $dataStage){
            $result = StatusTable::add($dataStage);
            if (!$result->isSuccess()){
                throw new \RuntimeException("Ошибка при создание статуса с ID {$dataStage['STATUS_ID']}: " . implode(', ', $result->getErrorMessages()) . "<br>");
            }
        }
    }
}