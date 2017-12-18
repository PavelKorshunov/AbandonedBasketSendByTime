# AbandonedBasketSendByTime
Класс для рассылки сообщений зарегистрированным пользователям,
по данным брошенной корзины Bitrix<br/>
Для подключения и работы, необходимо создать тип почтового события ABANDONED_BASKET с переменными BUYER_EMAIL, BUYER_NAME, ORDER_COMPOSITION - состав заказа<br/>
Затем создать почтовое событие привязанное к типу ABANDONED_BASKET.<br/>
Файл рекомендуется подключить, как отдельный файл в require_once "classes/AbandonedBasketSendByTime.php"; в init.php Битрикса.<br/>
После чего необходимо создать агента с переодичностью вызова каждый час, если сообщение должно приходить к примеру после 6 или 7 или 8 и т.д. часов брошенной корзины. Указать модуль main и вызвать метод init класса AbandonedBasketSendByTime: <b>AbandonedBasketSendByTime::init(7,6);</b>.
