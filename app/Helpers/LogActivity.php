<?php
namespace App\Helpers;
use Request;
use App\Models\LogActivity as LogActivityModel;

class LogActivity
{
    public static function addToLog($subject, $booking_id = null, $user_id = null)
    {
    	$log = [];
			if($booking_id)
    		$log['booking_id'] = $booking_id;
			// if($group_id)
    	// 	$log['group_id'] = $group_id;	
    	$log['subject'] = $subject;
    	$log['url'] = Request::fullUrl();
    	$log['method'] = Request::method();
    	$log['ip'] = Request::ip();
    	$log['agent'] = Request::header('user-agent');
			if($user_id)
    		$log['user_id'] = $user_id;
			else
				$log['user_id'] = auth()->check() ? auth()->user()->id : 1;	
    	LogActivityModel::create($log);
    }
    public static function logActivityLists()
    {
    	return LogActivityModel::latest()->get();
    }
}