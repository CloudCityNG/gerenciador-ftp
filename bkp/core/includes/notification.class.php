<?php

class notification
{
	private static $pageErrorArr = array();
	private static $pageSuccessArr = array();

	static function isErrors()
	{
		if (COUNT(self::$pageErrorArr))
		{
			return true;
		}
		
		return false;
	}
	
	static function setError($errorMsg)
	{
		self::$pageErrorArr[] = $errorMsg;
	}
	
	static function getErrors()
	{
		return self::$pageErrorArr;
	}
	
	static function outputErrors()
	{
		$errors = self::getErrors();
		if (COUNT($errors))
		{
			$htmlArr = array();
			foreach ($errors AS $error)
			{
				$htmlArr[] = "<li class='no-side-margin'><i class='fa fa-exclamation-triangle margin-right-20'></i>&nbsp;" . $error . "</li>";
			}
			
			return "<ul class='pageErrors'>" . implode("<br/>", $htmlArr) . "</ul>";
		}
	}
	
	static function isSuccess()
	{
		if (COUNT(self::$pageSuccessArr))
		{
			return true;
		}
		
		return false;
	}
	
	static function setSuccess($sucessMsg)
	{
		self::$pageSuccessArr[] = $sucessMsg;
	}
	
	static function getSuccess()
	{
		return self::$pageSuccessArr;
	}
	
	static function outputSuccess()
	{
		$success = self::getSuccess();
		if (COUNT($success))
		{
			$htmlArr = array();
			foreach ($success AS $success)
			{
				$htmlArr[] = "<li class='no-side-margin'><i class='fa fa-check-square margin-right-20'></i>&nbsp;" . $success . "</li>";
			}

			return "<ul class='pageSuccess'>" . implode("<br/>", $htmlArr) . "</ul>";
		}
	}
}
