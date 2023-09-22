<?php
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use Bitrix\Sale;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class LightSale extends CBitrixComponent
{
    // проверка подключения модуля sale
    private function _checkModules()
    {
        if (!Loader::includeModule('sale')) {
            throw new \Exception('Не загружены модули необходимые для работы модуля');
        }
        return true;
    }

    //возвращает id зарегистрированного пользователя. Если пользователь не авторизован - id общего пользователя "Unknown"
    public function GetUserId()
    {
        global $USER;
        $sort = ['sort' => 'asc'];
        $order = 'sort';
        $unauthorizedUser = CUser::GetList($sort, $order, ["LOGIN" => "Unknown"])->fetch();
        if (!$USER->GetID() && !$unauthorizedUser) {
            $user = new CUser;
            $chars = 'qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP';
            $size = strlen($chars) - 1;
            $password = '';
            while($length--) {
                $password .= $chars[random_int(0, $size)];
            }
            $arFields = array(
                "NAME" => "Неавторизованный пользователь",
                "LOGIN" => "Unknown",
                "EMAIL" => "Unknown@Unknown.ru",
                "LID" => "ru",
                "ACTIVE" => "Y",
                "PASSWORD" => $password,
                "CONFIRM_PASSWORD" => $password,
            );
            $ID = $user->Add($arFields);
        } elseif (!$USER->GetID() && $unauthorizedUser) {
            $ID = $unauthorizedUser["ID"];
        } else $ID = $USER->GetID();
        return $ID;
    }

    // получение списка доставок.
    public function GetDeliveries($payer = null, $deliveryForPaysystem = null)
    {
        // формирование массива доставок с ограничениями по плательщикам и платежным системам
        $deliveries = Sale\Delivery\Services\Manager::getActiveList();
        foreach ($deliveries as $key => &$delivery) {
            $dbRestr = \Bitrix\Sale\Delivery\Restrictions\Manager::getList(array(
                'filter' => array('SERVICE_ID' => $delivery['ID']) // ID службы доставки
            ));
            $params = [];
            while ($arRestr = $dbRestr->fetch()) {
                if(!$arRestr["PARAMS"]) {
                    $arRestr["PARAMS"] = array();
                }
                $param = $arRestr["CLASS_NAME"]::prepareParamsValues($arRestr["PARAMS"], $delivery['ID']); // Получаем платежные системы
                $params = array_merge($param, $params);
            }
            $params["PAY_SYSTEMS"] ? $delivery["PAY_SYSTEMS"] = $params["PAY_SYSTEMS"] : $delivery["PAY_SYSTEMS"] = [];
            $params["PERSON_TYPE_ID"] ? $delivery["PERSON_TYPE_ID"] = $params["PERSON_TYPE_ID"] : $delivery["PERSON_TYPE_ID"] = [];
        }
        // отсев доставок, не указанных в параметрах компонента
        if ($this->arParams["DELIVERIES"]) {
            foreach ($deliveries as $key => &$delivery) {
                if (!in_array($delivery["ID"], $this->arParams["DELIVERIES"])) unset($deliveries[$key]);
            }
        }
        // доставки для определенного плательщика
        if ($payer) {
            foreach ($deliveries as $key => &$delivery) {
                if (!$delivery["PERSON_TYPE_ID"]) continue;
                if (!in_array($payer, $delivery["PERSON_TYPE_ID"])) unset($deliveries[$key]);
            }
        }
        // доставки для платежной системы
        if ($deliveryForPaysystem) {
            foreach ($deliveries as $key => &$delivery) {
                if (!in_array($delivery["ID"], $deliveryForPaysystem)) unset($deliveries[$key]);
            }
        }

        return $deliveries;
    }

    // получение списка платежных систем
    public function GetPaySystems($payer = null, $paySystemsForDelivery = null)
    {
        // формирование массива платежных систем с ограничениями по плательщикам и доставкам
        $paySystemResult = Sale\PaySystem\Manager::getList(array('filter' => array('ACTIVE' => 'Y',)));
        while ($paySystem = $paySystemResult->fetch()) {
            $dbRestr = \Bitrix\Sale\Internals\ServiceRestrictionTable::getList(array(
                'filter' => array(
                    'SERVICE_ID' => $paySystem['ID'],
                    'SERVICE_TYPE' => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT
                )
            ));
            $restrictions = array();
            while ($restriction = $dbRestr->fetch()) {
                if (is_array($restriction['PARAMS']))
                    $restrictions = array_merge($restrictions, $restriction['PARAMS']);
            }
            $restriction = \Bitrix\Sale\Services\PaySystem\Restrictions\Delivery::prepareParamsValues(array(), $paySystem['ID']);
            $restrictions['DELIVERY'] = $restriction['DELIVERY'];

            $restrictions["PERSON_TYPE_ID"] ? $paySystem["PERSON_TYPE_ID"] = $restrictions["PERSON_TYPE_ID"] : $paySystem["PERSON_TYPE_ID"] = [];
            $restrictions["DELIVERY"] ? $paySystem["DELIVERY"] = $restrictions["DELIVERY"] : $paySystem["DELIVERY"] = [];
            $paySystems[] = $paySystem;
        }
        // отсев платежных систем, не указанных в параметрах компонента
        if ($this->arParams["PAYSYSTEMS"]) {
            foreach ($paySystems as $key => $paySystem) {
                if (!in_array($paySystem["ID"], $this->arParams["PAYSYSTEMS"])) unset($paySystems[$key]);
            }
        }
        // платежные системы для определенного плательщика
        if ($payer) {
            foreach ($paySystems as $key => &$paySystem) {
                if (!$paySystem["PERSON_TYPE_ID"]) continue;
                if (!in_array($payer, $paySystem["PERSON_TYPE_ID"])) unset($paySystems[$key]);
            }
        }
        // платежные системы для определенной доставки
        if ($paySystemsForDelivery) {
            foreach ($paySystems as $key => &$paySystem) {
                if (!in_array($paySystem["ID"], $paySystemsForDelivery)) unset($paySystems[$key]);
            }
        }
        return $paySystems;
    }

    // получение списка плательщиков.
    public function GetPersonTypes()
    {
        // формирование массива плательщиков
        $types = CSalePersonType::GetList();
        while ($ob = $types->GetNext()) {
            $persons[] = $ob;
        }
        // отсев плательщиков по параметрам
        if ($this->arParams["PERSONS"]) {
            foreach ($persons as $key => $person) {
                if (!in_array($person["ID"], $this->arParams["PERSONS"])) unset($persons[$key]);
            }
        }

        return $persons;
    }

    // получение списка полей ввода
    public function GetFields($payer = null)
    {
        if ($payer) { // для плательщика
            $dbFields = CSaleOrderProps::GetList(
                array("SORT" => "ASC"),
                array(
                    "PERSON_TYPE_ID" => $payer,
                    "ACTIVE" => "Y",
                ));

        } else { // всех полей
            $dbFields = CSaleOrderProps::GetList(array("SORT" => "ASC"),
                array(
                    "ACTIVE" => "Y",
                ));
        }
        //формирование массива полей
        while ($ob = $dbFields->GetNext()) {
            $result[] = $ob;
        }
        return $result;
    }

    // получение корзины для конкретного пользователя
    public function GetBasketItems($user = null)
    {
        $arFUser = CSaleUser::GetList(['USER_ID' => $user]);
        $basketItems = Bitrix\Sale\Basket::loadItemsForFUser($arFUser['ID'], SITE_ID);
        return $basketItems;
    }

    // добавление оплаты в заказ
    public function SetPaySystem($order, $paymentId)
    {
        $paymentCollection = $order->getPaymentCollection();
        $payment = $paymentCollection->createItem(
            Bitrix\Sale\PaySystem\Manager::getObjectById(
                intval($paymentId)
            )
        );
        $payment->setField("SUM", $order->getPrice());  // сумма оплаты
        $payment->setField("CURRENCY", $order->getCurrency()); // валюта
    }

    // добавление отгрузки к заказу (с установкой выбранной службы доставки)
    public function setBasketShipment($order, $deliveryId, $basketItems)
    {
        $shipmentCollection = $order->getShipmentCollection();
        $shipment = $shipmentCollection->createItem(Bitrix\Sale\Delivery\Services\Manager::getObjectById($deliveryId));

        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        // добавление товаров в отгрузку
        foreach ($basketItems as $basketItem) {
            $item = $shipmentItemCollection->createItem($basketItem);
            $item->setQuantity($basketItem->getQuantity());
        }
    }

    // добавление полей, введенных пользователем, к заказу
    public function setOrderProperties($order, $properties)
    {
        foreach ($order->getPropertyCollection() as $prop) {
            $id = $prop->getField("ORDER_PROPS_ID");
            if ($properties[$id]) {
                $prop->setValue($properties[$id]);
            }
        }
    }

    // создание объекта заказа, который будет использоваться в других методах
    // метод поочередно обращается к других методам, которые добавляют к заказу корзину, поля ввода и т.д.
    public function SetOrder($PersonTypeId, $deliveryId, $paymentId, $properties)
    {
        $order = Bitrix\Sale\Order::create(SITE_ID, $this->GetUserId());
        $basketItems = $this->GetBasketItems($this->GetUserId());

        if ($PersonTypeId != null) {
            $order->setPersonTypeId($PersonTypeId);
        }
        $order->setBasket($basketItems);
        if ($deliveryId != null) {
            $this->setBasketShipment($order, $deliveryId, $basketItems);
        }
        if ($properties != null) {
            $this->setOrderProperties($order, $properties);
        }
        if ($paymentId != null) {
            $this->SetPaySystem($order, $paymentId);
        }
        $order->save();
    }

    // вывод всех доставок, платежных систем, плательщиков и полей для ввода из админки в arResult
    public function SetArResult()
    {
        $this->arResult["PAYSYSTEMS"] = $this->GetPaySystems();
        $this->arResult["DELIVERIES"] = $this->GetDeliveries();
        $this->arResult["PERSONTYPE"] = $this->GetPersonTypes();
        $this->arResult["FIELDS"] = $this->GetFields();
    }

    // Изменение порядка вывода доставки и оплаты, в зависимости от настроек компонента
    public function saleOrder()
    {
        if ($this->arParams["ORDER"] == "DELIVERY") {
            $this->arResult["ORDER"]["FIRST"] = $this->arResult["DELIVERIES"];
            $this->arResult["ORDER"]["SECOND"] = $this->arResult["PAYSYSTEMS"];
        } else {
            $this->arResult["ORDER"]["FIRST"] = $this->arResult["PAYSYSTEMS"];
            $this->arResult["ORDER"]["SECOND"] = $this->arResult["DELIVERIES"];
        }
    }

    //функция инициализации компонента. при получение пост параметра MAKE_ORDER создает заказ.
    // параметр передается в ajax запросе при оформлении заказа
    public function executeComponent()
    {
        $this->_checkModules();
        if ($_POST["MAKE_ORDER"] == "Y") {
            foreach ($_POST as $key => $data) {
                if ($key != "PERSONTYPEID" && $key != "DELIVERYID" && $key != "PAYMENTID") {
                    $properties[$key] = $data;
                }
            }
            $this->SetOrder($_POST["PERSONTYPEID"], $_POST["DELIVERYID"], $_POST["PAYMENTID"], $properties);
        }
        $this->SetArResult();
        $this->saleOrder();
        $this->includeComponentTemplate();
    }
}
