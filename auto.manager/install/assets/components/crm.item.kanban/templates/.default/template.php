<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}
\Bitrix\Main\UI\Extension::load(
    [
        'ui.dialogs.messagebox',
    ]
);

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-hidden no-background");
if($this->getComponent()->getErrors())
{
    foreach($this->getComponent()->getErrors() as $error)
    {
        /** @var \Bitrix\Main\Error $error */
        ?>
        <div class="ui-alert ui-alert-danger">
            <span class="ui-alert-message"><?=$error->getMessage();?></span>
        </div>
        <?php
    }

    return;
}
/** @see \Bitrix\Crm\Component\Base::addTopPanel() */
$this->getComponent()->addTopPanel($this);

/** @see \Bitrix\Crm\Component\Base::addToolbar() */
$this->getComponent()->addToolbar($this);
?>
    <div class="ui-toolbar-after-title-buttons" style="margin-bottom: 10px;"><button id="start-process-btn" class="ui-btn ui-btn-primary">Импорт</button></div>
    <div class="ui-alert ui-alert-danger" style="display: none;">
        <span class="ui-alert-message" id="crm-type-item-list-error-text-container"></span>
        <span class="ui-alert-close-btn" onclick="this.parentNode.style.display = 'none';"></span>
    </div>

    <script>

        var progress = new BX.UI.StepProcessing.Process({
            id: 'import',
            controller: 'auto:manager.api.uploadexelfile',
            queue: [
                {
                    action: 'upload',
                    title: 'Загрузка и обработка файла',
                    progressBarTitle: 'Загрузка...',
                },
                {
                    action: 'finalize',  // Завершение процесса
                    finalize: true
                }
            ],
            optionsFields: {
                "File" : {
                    name: 'File',
                    type: 'file',
                    title: 'Выберите файл Excel',
                    obligatory: true,
                    emptyMessage: 'Пожалуйста, выберите файл для загрузки',
                }
            },
            showButtons: {
                start: true,
                stop: true,
                close: true,
            },
        })
            .setMessages({
                'DialogTitle': 'Импорт данных',
                'DialogSummary': 'Пожалуйста, выберите файл для импорта.',
                'DialogStartButton': 'Старт',
                'DialogStopButton': 'Стоп',
                'DialogCloseButton': 'Закрыть',
                'RequestCanceling': 'Отменяю...',
                'RequestCanceled': 'Процесс остановлен',
                'RequestCompleted': 'Готово!',
            }).setHandler(BX.UI.StepProcessing.ProcessCallback.RequestStart, function(actionData) {
                var fileInput = progress.getDialog().getOptionField('File'); // Получаем файл из формы
                var file = fileInput.getValue();
                if (file) {
                    actionData.append('File', file[0]); // Добавляем файл в запрос
                }
            })


        document.querySelector('#start-process-btn').addEventListener('click', () => {
            progress.showDialog()
        });
    </script>

<?php

$headerSections = [
    [
        'id'=> $arResult['entityTypeName'],
        'name' => $arResult['entityTypeDescription'],
        'default' => true,
        'selected' => true,
    ]
];

$APPLICATION->IncludeComponent(
    'bitrix:crm.kanban',
    '',
    [
        'ENTITY_TYPE' => $arResult['entityTypeName'],
        'SHOW_ACTIVITY' => $arResult['isCountersEnabled'] ? 'Y' : 'N',
        'EXTRA' => [
            'CATEGORY_ID' => $arResult['categoryId'],
            'ADD_ITEM_PERMITTED_BY_TARIFF' => $arResult['addItemPermittedByTariff'],
            'ANALYTICS' => $arResult['analytics'] ?? [],
        ],
        'HEADERS_SECTIONS' => $headerSections,
        'PERFORMANCE' => $arResult['performance'],
    ],
    $component
);
