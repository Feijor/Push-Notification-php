<?php 
class PushNotifications {
    
    define("PRODUCTION_MODE",false);
  
	private static $API_ACCESS_KEY = 'your_api_key';

	public function __construct() {
		exit('Init function is not allowed');
	}
	
	public function android($data, $reg_id) {
	        $url = 'https://android.googleapis.com/fcm/send';
	        $message = array(
	            'iditem' => $data['mid'],
	            'title' => $data['mtitle'],
	            'message' => $data['mdesc'],
	            'subtitle' => '',
	            'tickerText' => '',
	            'msgcnt' => 1,
	            'vibrate' => 1
	        );
	        
	        $headers = array(
	        	'Authorization: key=' .self::$API_ACCESS_KEY,
	        	'Content-Type: application/json'
	        );
	
	        $fields = array(
	            'registration_ids' => array($reg_id),
	            'data' => $message,
	        );
	
	    	return $this->useCurl($url, $headers, json_encode($fields));
    	}
	
	public function WP($data, $uri) {
		$delay = 2;
		$msg =  "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
		        "<wp:Notification xmlns:wp=\"WPNotification\">" .
		            "<wp:Toast>" .
		                "<wp:Text1>".htmlspecialchars($data['mtitle'])."</wp:Text1>" .
		                "<wp:Text2>".htmlspecialchars($data['mdesc'])."</wp:Text2>" .
		                "<wp:iditem>".htmlspecialchars($data['mid'])."</wp:iditem>" .
		            "</wp:Toast>" .
		        "</wp:Notification>";
		
		$sendedheaders =  array(
		    'Content-Type: text/xml',
		    'Accept: application/*',
		    'X-WindowsPhone-Target: toast',
		    "X-NotificationClass: $delay"
		);
		
		$response = $this->useCurl($uri, $sendedheaders, $msg);
		
		$result = array();
		foreach(explode("\n", $response) as $line) {
		    $tab = explode(":", $line, 2);
		    if (count($tab) == 2)
		        $result[$tab[0]] = trim($tab[1]);
		}
		
		return $result;
	}
	
	public function iOS($data, $devicetoken) {
      
        if(PRODUCTION_MODE) {
          $apnsHost = 'gateway.sandbox.push.apple.com';
          $file = 'ckDev.pem';
          $pass = '';
        } else {
          $apnsHost = 'gateway.push.apple.com';
          $file = 'ckDis.pem';
          $pass = '';
        }

		$deviceToken = $devicetoken;

		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', $file);
		stream_context_set_option($ctx, 'ssl', 'passphrase', $pass);

		$fp = stream_socket_client(
			'ssl://'.$apnsHost.':2195', $err,
			$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

		if (!$fp)
			exit("Failed to connect: $err $errstr" . PHP_EOL);

		$body['aps'] = array(
			'alert' => array(
			    'title' => $data['mtitle'],
                'body' => $data['mdesc'],
                'iditem' => $data['mid'],
			 ),
			'sound' => 'default'
		);

		$payload = json_encode($body);

		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

		$result = fwrite($fp, $msg, strlen($msg));
		
		fclose($fp);

        if (!$result)
          $ret = array('success' => 0,'msg' => 'Message not delivered' . PHP_EOL);
        else
          $ret = array('success' => 1,'msg' => 'Message successfully delivered' . PHP_EOL);
        

	}
	
	private function useCurl(&$model, $url, $headers, $fields = null) {
	        $ch = curl_init();
	        if ($url) {
	            curl_setopt($ch, CURLOPT_URL, $url);
	            curl_setopt($ch, CURLOPT_POST, true);
	            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	     
	            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	            if ($fields) {
	                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
	            }
	     
	            $result = curl_exec($ch);
	            if ($result === FALSE) {
	                die('Curl failed: ' . curl_error($ch));
	            }
	     
	            curl_close($ch);
	
	            return $result;
        }
    }
    
}
?>
