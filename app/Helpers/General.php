<?php
namespace App\Helpers;
use Request;

class General
{
	public static function remove_unwanted_character_and_set_sms_mysms($partner_message){
		$remove_character = array("\n", "\r\n", "\r");
		$partner_message = str_replace($remove_character , '%0a', $partner_message);
		$partner_message = str_replace('£', '%C2%A3', $partner_message);
		$partner_message = str_replace(' ', '%20', $partner_message);
		$partner_message = str_replace('!', '%21', $partner_message);
		$partner_message = str_replace('"', '%22', $partner_message);
		$partner_message = str_replace('#', '%23', $partner_message);
		$partner_message = str_replace('&', '%26', $partner_message);
		$partner_message = str_replace('+', '%2B', $partner_message);
		$partner_message = str_replace('<', '%3C', $partner_message);
		$partner_message = str_replace('>', '%3E', $partner_message);
		$partner_message = str_replace('=', '%3D', $partner_message);
		$partner_message = str_replace('^', '%5E', $partner_message);
		$partner_message = str_replace('„', '%E2%80%9E', $partner_message);
		$partner_message = str_replace('†', '%E2%80%A0', $partner_message);
		$partner_message = str_replace('‡', '%E2%80%A1', $partner_message);
		$partner_message = str_replace('ˆ', '%CB%86', $partner_message);
		$partner_message = str_replace('‰', '%E2%80%B0', $partner_message);
		$partner_message = str_replace('Š', '%C5%A0', $partner_message);
		$partner_message = str_replace('‹', '%E2%80%B9', $partner_message);
		$partner_message = str_replace('Œ', '%C5%92', $partner_message);
		$partner_message = str_replace('Ž', '%E2%80%B0', $partner_message);
		$partner_message = str_replace('', '%8F', $partner_message);
		$partner_message = str_replace('', '%C2%90', $partner_message);
		$partner_message = str_replace('‘', '%E2%80%98', $partner_message);
		$partner_message = str_replace('’', '%E2%80%99', $partner_message);
		$partner_message = str_replace('“', '%E2%80%9C', $partner_message);
		$partner_message = str_replace('”', '%E2%80%9D', $partner_message);
		$partner_message = str_replace('•', '%E2%80%A2', $partner_message);
		$partner_message = str_replace('–', '%E2%80%93', $partner_message);
		$partner_message = str_replace('—', '%E2%80%94', $partner_message);
		$partner_message = str_replace('˜', '%CB%9C', $partner_message);
		$partner_message = str_replace('˜', '%CB%9C', $partner_message);
		$partner_message = str_replace('˜', '%CB%9C', $partner_message);
		$partner_message = str_replace('˜', '%CB%9C', $partner_message);
		$partner_message = str_replace('˜', '%CB%9C', $partner_message);
		$partner_message = str_replace('˜', '%CB%9C', $partner_message);
		$partner_message = str_replace('˜', '%CB%9C', $partner_message);
		$partner_message = str_replace('˜', '%CB%9C', $partner_message);
		$partner_message = str_replace('˜', '%CB%9C', $partner_message);
		$partner_message = str_replace('˜', '%CB%9C', $partner_message);
		$partner_message = str_replace('™', '%CB%9C', $partner_message);
		return $partner_message;
	}

	public static function replace_special_characters($str){
		$strParams = [
			'&#39;' => ' a',
			];
			return strtr($str, $strParams);
	}
}