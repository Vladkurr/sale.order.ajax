<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @var $APPLICATION
 * @var $templateFolder
 * @var $arParams
 * @var $arResult
 */
?>
<div class="tabs-container">
    <?php foreach ($arResult["PERSONTYPE"] as $person): ?>
        <button id="tab_<?= $person["ID"] ?>" class="tab-button"><?= $person["NAME"] ?></button>
    <?php endforeach; ?>
</div>


<div class="persons-container">
    <?php $count = 0 ?>
    <?php foreach ($arResult["PERSONTYPE"] as $key => $person): ?>
        <form class="form_order <?= !$count ? "active" : "" ?>" action="" id="form_<?= $person["ID"] ?>"
              style="display: <?= !$count ? "" : "none" ?>">
            <h2 id="person_<?= $person["ID"] ?>" class="person"></h2>
            <h2>свойства</h2>
            <!--    FIELDS     -->
            <?php foreach ($arResult["FIELDS"] as $arField): ?>
                <?php if ($person["ID"] == $arField["PERSON_TYPE_ID"]): ?>
                    <input class="active" type="text" id="field_<?= $arField["ID"] ?>"
                           placeholder="<?= $arField["NAME"] ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <h2>Доставка</h2>
            <!--    DELIVERIES     -->
            <?php $c = 0; ?>
            <?php foreach ($arResult["DELIVERIES"] as $arDelivery): ?>
                <?php if (in_array($person["ID"], $arDelivery["PERSON_TYPE_ID"]) || empty($arDelivery["PERSON_TYPE_ID"])): ?>
                    <?php if ($c == 1): ?>
                        <input class="active" id="delivery_<?= $arDelivery["ID"] ?>" name="DELIVERYID" type="radio"
                               checked/>
                    <?php else: ?>
                        <input class="active" id="delivery_<?= $arDelivery["ID"] ?>" name="DELIVERYID" type="radio"/>
                    <?php endif; ?>
                    <label for="<?= $arDelivery["CODE"] ?>"><?= $arDelivery["NAME"] ?></label>
                    <?php $c++ ?>
                <?php endif; ?>
            <?php endforeach; ?>
            <h2>Оплата</h2>
            <!--    PAYMENTS     -->
            <?php $c = 0; ?>
            <?php foreach ($arResult["PAYSYSTEMS"] as $arPaySystem): ?>
                <?php if (in_array($person["ID"], $arPaySystem["PERSON_TYPE_ID"]) || empty($arPaySystem["PERSON_TYPE_ID"])): ?>
                    <?php if ($c == 0): ?>
                        <input class="active" id="paysystem_<?= $arPaySystem["ID"] ?>" name="PAYMENTID" type="radio"
                               checked/>
                    <?php else: ?>
                        <input class="active" id="paysystem_<?= $arPaySystem["ID"] ?>" name="PAYMENTID" type="radio"/>
                    <?php endif; ?>
                    <label for="<?= $arPaySystem["CODE"] ?>"><?= $arPaySystem["NAME"] ?></label>
                    <?php $c++ ?>
                <?php endif; ?>
            <?php endforeach; ?>
            <button class="submit_custom_order">Отправить</button>
        </form>
        <?php $count++ ?>
    <?php endforeach; ?>
</div>

<?php include('script.php'); ?>
