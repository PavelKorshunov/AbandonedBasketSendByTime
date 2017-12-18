<?php
/* @author Pavel Korshunov <hard-skills.ru>
 * Класс для рассылки сообщений зарегистрированным пользователям,
 * по данным брошенной корзины Bitrix
*/
class AbandonedBasketSendByTime
{

	/*
	 * Принимает массив параметров заказа
	 * Возвращает строку с количеством и наименованием товаров
	*/
	private static function ParseArrOrderComposition($arrComposition)
	{
		$string = '';
		foreach ($arrComposition as $value)
		{
			$string .= round($value["QUANTITY"]) . ' - ';
			$string .= $value["PRODUCT_NAME"] . '<br/>';
		}
		return $string;
	}

	/*
	 * Принимает массив GetUserInfoByIds и массив товаров GetAbandonedBasketByTime
	 * Формирует и отправляет письма пользователям
	*/
	public static function SendMailToUser($arPropsUsers, $arrProducts)
	{

		foreach ($arPropsUsers as $user)
		{

			$arOrderComposition = [];
			$arOrderCompositionString = "";
			foreach ($arrProducts as $product) 
			{
				if($product["USER_ID"] === $user["ID"])
				{
					$arProduct = array(
						"QUANTITY" => $product["QUANTITY"],
						"PRODUCT_NAME" => $product["NAME"],
					);

					array_push($arOrderComposition, $arProduct);
				}
			}

			$arEventFields = array(
				"BUYER_EMAIL" => $user["EMAIL"],
				"BUYER_NAME" => $user["NAME"],
				"ORDER_COMPOSITION" => self::ParseArrOrderComposition($arOrderComposition)
			);
			//rr($arEventFields);

			CEvent::Send("ABANDONED_BASKET", SITE_ID, $arEventFields, 'N');
			
		}
	}

	/*
	 * Принимает массив пользователей вида, как функция GetUsersInAbandonedBasket
	 * Возвращает массив с информацией по пользователям
	*/
	public static function GetUserInfoByIds($arrUsers)
	{
		$arReturn = [];

		foreach ($arrUsers as $key => $userProp)
		{
			$rsUser = CUser::GetByID($userProp);
			$arUser = $rsUser->Fetch();
			array_push($arReturn, $arUser);
		}

		return $arReturn;
	}


	/*
	 * Принимает массив из функции GetAbandonedBasketByTime
	 * Возвращает массив id уникальных пользователей
	*/
	public static function GetUsersInAbandonedBasket($arrProducts)
	{
		$arReturn = [];

		$userId = 0;
		foreach ($arrProducts as $product)
		{
			if(isset($product['USER_ID']) && !empty($product['USER_ID']))
			{
				if($product['USER_ID'] != $userId)
				{
					array_push($arReturn, $product['USER_ID']);
				}
				$userId = $product['USER_ID'];
			}
			else {
				$arReturn = 'В принимаемом массиве отсутствует ячейка USER_ID или она пуста';
			}
		}

		return $arReturn;
	}


	/*
	 * Принимает дату и время в нужном формате, как в функции ConvertTimeStamp
	 * Возвращает массив товаров из корзины удовлетворяющий времени
	*/
	public static function GetAbandonedBasketByTime($start, $end)
	{
		if(CModule::IncludeModule("sale"))
		{
			$arReturn = [];
			$arFilter = array(
				">DATE_UPDATE" => ConvertTimeStamp(time()-60*60*$start, "FULL"),
				"<DATE_UPDATE" => ConvertTimeStamp(time()-60*60*$end, "FULL"),
				"!USER_ID" => "");
			$arSelect = array();
			$dbBasketItems = CSaleBasket::GetList(array(), $arFilter, false, false, $arSelect);

			while ($arItems = $dbBasketItems->Fetch())
			{
				array_push($arReturn, $arItems);
			}

			return $arReturn;
		}
	}

	/*
	 * Инициализирует работу класса
	 * Получает параметры максимального времени и минимального времени в часах
	 * Например: если указать init(7, 6), то письмо о брошенной корзине будет приходить
	 * пользователям у которых брошенная корзина лежит больше 6 часов, но меньше 7
	*/
	public static function init($start, $end)
	{
		$arRes = self::GetAbandonedBasketByTime($start, $end);
		$arUsers = self::GetUsersInAbandonedBasket($arRes);
		$arUserProps = self::GetUserInfoByIds($arUsers);
		$arSend = self::SendMailToUser($arUserProps, $arRes);

		//return $arSend;
		return "AbandonedBasketSendByTime::init(".$start.",".$end.");";
	}
}