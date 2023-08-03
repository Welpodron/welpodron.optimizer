<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;

$moduleId = 'welpodron.optimizer';

$arTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => 'Основные настройки',
        'TITLE' => 'Основные настройки',
        'GROUPS' => [
            [
                'TITLE' => 'Минификация данных',
                'OPTIONS' => [
                    [
                        'NAME' => 'USE_MINIFY_HTML',
                        'LABEL' => 'Минифицировать HTML (Использовать с осторожностью!)',
                        'VALUE' => Option::get($moduleId, 'USE_MINIFY_HTML'),
                        'TYPE' => 'checkbox',
                    ],
                ],
            ],
        ],
    ],
];

$request = Context::getCurrent()->getRequest();

if ($request->isPost() && $request['save'] && check_bitrix_sessid()) {
    foreach ($arTabs as $arTab) {
        foreach ($arTab['GROUPS'] as $arGroup) {
            foreach ($arGroup['OPTIONS'] as $arOption) {
                if ($arOption['TYPE'] == 'note') continue;

                $value = $request->getPost($arOption['NAME']);

                if ($arOption['TYPE'] == "checkbox" && $value != "Y") {
                    $value = "N";
                } elseif (is_array($value)) {
                    $value = implode(",", $value);
                } elseif ($value === null) {
                    $value = '';
                }

                Option::set($moduleId, $arOption['NAME'], $value);
            }
        }
    }

    LocalRedirect($APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID . '&mid_menu=1&mid=' . urlencode($moduleId) .
        '&tabControl_active_tab=' . urlencode($request['tabControl_active_tab']));
}

$tabControl = new CAdminTabControl("tabControl", $arTabs, true, true);
?>

<form name=<?= str_replace('.', '_', $moduleId) ?> method='post'>
    <? $tabControl->Begin(); ?>
    <?= bitrix_sessid_post(); ?>
    <? foreach ($arTabs as $arTab) : ?>
        <? $tabControl->BeginNextTab(); ?>
        <? foreach ($arTab['GROUPS'] as $arGroup) : ?>
            <tr class="heading">
                <td colspan="2"><?= $arGroup['TITLE'] ?></td>
            </tr>
            <? foreach ($arGroup['OPTIONS'] as $arOption) : ?>
                <tr>
                    <td style="width: 40%;">
                        <? if ($arOption['TYPE'] != 'note') : ?>
                            <label for="<?= $arOption['NAME'] ?>">
                                <?= $arOption['LABEL'] ?>
                            </label>
                        <? endif ?>
                    </td>
                    <td>
                        <? if ($arOption['TYPE'] == 'note') : ?>
                            <div class="adm-info-message">
                                <?= $arOption['LABEL'] ?>
                            </div>
                        <? elseif ($arOption['TYPE'] == 'checkbox') : ?>
                            <input <? if ($arOption['VALUE'] == "Y") echo "checked "; ?> type="checkbox" name="<?= htmlspecialcharsbx($arOption['NAME']) ?>" id="<?= htmlspecialcharsbx($arOption['NAME']) ?>" value="Y">
                        <? else : ?>
                            <input id="<?= htmlspecialcharsbx($arOption['NAME']) ?>" name="<?= htmlspecialcharsbx($arOption['NAME']) ?>" type="text" size="80" maxlength="255" value="<?= $arOption['VALUE'] ?>">
                        <? endif; ?>
                    </td>
                </tr>
            <? endforeach; ?>
        <? endforeach; ?>
    <? endforeach; ?>
    <? $tabControl->Buttons(['btnApply' => false, 'btnCancel' => false, 'btnSaveAndAdd' => false]); ?>
    <? $tabControl->End(); ?>
</form>