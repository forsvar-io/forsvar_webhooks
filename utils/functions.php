<?php
	// use PHPMailer\PHPMailer\PHPMailer;
	// use PHPMailer\PHPMailer\Exception;
	
function datePretty($var) {
   	$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
	$fecha =  date('d',strtotime($var))." de ".$meses[date('n',strtotime($var))-1]. " del ".date('Y',strtotime($var)) ; 
	return $fecha;
}

function datedmy($var) {
	$newDate = date("d/m/Y", strtotime($var));
	return $newDate;
}

function datePrettySmall($var) {
   	$meses = array("Ene.","Feb.","Mar.","Abr.","May.","Jun.","Jul.","Ago.","Sep.","Oct.","Nov.","Dic.");
	$fecha =  date('d',strtotime($var))." ".$meses[date('n',strtotime($var))-1]. " ".date('Y',strtotime($var)) ; 
	return $fecha;
}

function getAvatar($userid){
if (file_exists("images/avatar/$userid.jpg")) {
	$avatar_photo_url = "images/avatar/$userid.jpg?t=" . time();
} else {$avatar_photo_url = "images/avatar/noavatar.jpg";}	
return $avatar_photo_url;
}

function monthname($var) {
	$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
	return $meses[$var-1];
}

function getEstadoText($var) {
	$bg = "bg-info";
	if ($var == 0) {
		$return = "Pendiente cobro";
		$bg = "bg-warning";
	} else 	if ($var == 1) {
		$return = "Parcialmente cobrada";
	} else 	if ($var == 2) {
		$return = "Cobrada";
		$bg = "bg-success";
	} else 	if ($var == 3) {
		$return = "Anulada";
		$bg = "bg-danger";
	} else 	if ($var == 4) {
		$return = "Parcialmente devuelta";
		$bg = "bg-warning";
	} else 	if ($var == 5) {
		$return = "Anulacion";
		$bg = "bg-danger";
	} else 	if ($var == 6) {
		$return = "Pagada";
		$bg = "bg-success";
	} else 	if ($var == 7) {
		$return = "Pendiente pago";
	} else 	if ($var == 8) {
		$return = "Parcialmente pagada";
	} else 	if ($var == 9) {
		$return = "Aceptada";
		$bg = "bg-success";
	} else {
		$return = "N/A";
	}
	return array($return,$bg);
}

function base64url_encode($bin) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($bin));
}

function base64url_decode($str) {
    return base64_decode(str_replace(['-', '_'], ['+', '/'], $str));
}

function sendEmail($emailreceiver, $userfullname, $subject, $html, $text, $file, $filename,$provider = NULL) {
	if ($provider == 'gmail') {
		sendEmailGmail($emailreceiver, $userfullname, $subject, $html, $text, $file, $filename);
	} else {
		sendEmailGmail($emailreceiver, $userfullname, $subject, $html, $text, $file, $filename);
	}
	
}

// function sendEmailGmail($emailreceiver, $userfullname, $subject, $html, $text, $file, $filename) {

// 	include_once('PhpMailer/PHPMailer.php');
// 	include_once('PhpMailer/Exception.php');
// 	include_once('PhpMailer/SMTP.php');
	
// 	$mail = new PHPMailer();
// 	$mail->IsSMTP();
// 	$mail->Mailer = "smtp";	
	
// 	$mail->SMTPDebug  = 0;  
// 	$mail->SMTPAuth   = TRUE;
// 	$mail->SMTPSecure = "tls";
// 	$mail->Port       = 587;
// 	$mail->Host       = "smtp.gmail.com";
// 	$mail->Username   = "info@pypike.com";
// 	$mail->Password   = "dzlbeezusuzbtilk";	
		
// 	$mail->IsHTML(true);
// 	$mail->AddAddress($emailreceiver, $userfullname);
// 	$mail->SetFrom("info@pypike.com", "PyPike");
// //	$mail->AddReplyTo("reply-to-email@domain", "reply-to-name");
// //	$mail->AddCC("cc-recipient-email@domain", "cc-recipient-name");
// 	$mail->Subject = $subject;
// 	if ($file <> '') {
// 		$attachment_content = chunk_split(base64_encode($file));
// 		$mail->addStringAttachment($file,$filename);	
// 	}
// 	$content = $html;
// 	$mail->MsgHTML($content); 
	
// 	$result = array();
// 	if(!$mail->Send()) {
// 	  $result['result'] = 0;
// 	  $result['error'] = $mail->ErrorInfo;
// 	  return json_encode($result);
// 	} else {
// 	  $result['result'] = 1;
// 	  return json_encode($result);
// 	}	
// }

// function sendEmailSendinblue($emailreceiver, $userfullname, $subject, $html, $text, $file, $filename) {
// //	$myemailsender = 'sender@facturalive.com';
// //	$apikey = 'DBVK4FdxHGp8YqLy';
// 	$apptitle = 'FacturaLive - Facturacion Electronica';	
// /*	$f = fopen("$filename", 'w');
// 	fwrite($f, $file);
// 	fclose($f);	*/
	
// 	include_once('mailin/Mailin.php');
// 	$mailin = new Mailin('sender@facturalive.com', 'DBVK4FdxHGp8YqLy');
// 	$mailin->
// 	addTo($emailreceiver, $userfullname)->
// 	setFrom('sender@facturalive.com', $apptitle)->
// 	setReplyTo('sender@facturalive.com',$apptitle)->
// 	//setBcc('nachodeleon77@gmail.com')->
// 	setSubject($subject)->
// 	setText('Comprobante fiscal electronico')->
// 	setHtml($html);
// 	$attachment_content = chunk_split(base64_encode($file));
// 	$mailin->createAttachment(array("$filename"=>$attachment_content));
	
// 	$res = $mailin->send();
// //	unlink($filename);
// //	echo $res;
// 	return $res;
// 	/*
// 	El mensaje de éxito se enviará de esta forma:
// 	{'result' => true, 'message' => 'E-MAILS enviados'}
// 	*/	
// }

function randomPassword() {
	$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
	$pass = array(); //remember to declare $pass as an array
	$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
	for ($i = 0; $i < 8; $i++) {
		$n = rand(0, $alphaLength);
		$pass[] = $alphabet[$n];
	}
	return implode($pass); //turn the array into a string
}

function get_timeago( $ptime, $language = NULL )
{
    $etime = time() - $ptime;

    if( $etime < 1 )
    {
        return 'less than 1 second ago';
    }

    $a = array( 12 * 30 * 24 * 60 * 60  =>  'year',
                30 * 24 * 60 * 60       =>  'month',
                24 * 60 * 60            =>  'day',
                60 * 60             =>  'hour',
                60                  =>  'minute',
                1                   =>  'second'
    );

    foreach( $a as $secs => $str )
    {
        $d = $etime / $secs;

        if( $d >= 1 )
        {
            $r = round( $d );
            return 'about ' . $r . ' ' . $str . ( $r > 1 ? 's' : '' ) . ' ago';
        }
    }
}

function getRiskRange($score,$risk_score_range) {
	$r = array(
		"ID"=> 0,
		"color"=>'#999999'
	);
	foreach ($risk_score_range as $range) {
		if ($score >= $range['range_lower'] && $score <= $range['range_upper'] ) {
			$r = $range;
		}
	}
	return $r;
}

function checkPostVariables($variableNames,$object) {
	foreach ($variableNames as $variableName) {
		if (!isset($object[$variableName])) {
			return false; // At least one variable is missing
		}
	}

	// All required variables are set
	return true;
}

function isBase64($str) {
	if (base64_decode($str, true)) {
		return true;
	} else {
		return false;
	}        
}    

function getGeolocationInfo($latitude, $longitude) {
	$apiKey = 'AIzaSyAnydyACjDEVvZCe2B3zs23KyD_Yf5YWIw';

	// Google Geocoding API endpoint for reverse geocoding
	$apiEndpoint = 'https://maps.googleapis.com/maps/api/geocode/json';

	// Construct the request URL
	$requestUrl = "{$apiEndpoint}?latlng={$latitude},{$longitude}&key={$apiKey}";

	// Initialize cURL session
	$ch = curl_init();

	// Set cURL options
	curl_setopt($ch, CURLOPT_URL, $requestUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	// Execute cURL session and get the response
	$response = curl_exec($ch);

	// Check for cURL errors
	if (curl_errno($ch)) {
		echo 'cURL Error: ' . curl_error($ch);
		return null;
	}

	// Close cURL session
	curl_close($ch);

	// Decode the JSON response
	$data = json_decode($response, true);

	// Check if decoding was successful
	if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
		echo 'JSON decoding error: ' . json_last_error_msg();
		return null;
	}

	// Check if the API request was successful
	if ($data['status'] === 'OK') {
		// Extract address components
		$formattedAddress = $data['results'][0]['formatted_address'];
		$addressInfo = $data['results'][0]['address_components'];

		// Initialize variables to store address, city, state, and country
		$address = $city = $state = $country = '';

		// Loop through address components
		foreach ($addressInfo as $component) {
			$types = $component['types'];

			if (in_array('street_number', $types)) {
				$street_number = $component['long_name'];
			} elseif (in_array('street_address', $types) || in_array('route', $types)) {
				$address = $component['long_name'];
			} elseif (in_array('locality', $types) || in_array('sublocality', $types)) {
				$city = $component['long_name'];
			} elseif (in_array('administrative_area_level_1', $types)) {
				$state = $component['long_name'];
			} elseif (in_array('country', $types)) {
				$country = $component['long_name'];
				$country_short = $component['short_name'];
			} elseif (in_array('postal_code', $types)) {
				$postcode = $component['long_name'];
			}
		}


		// You can return the data or use it as needed
		return [
			'street_number' => $street_number,
			'address' => $address,
			'city' => $city,
			'state' => $state,
			'country' => $country,
			'country' => $country,
			'country_short' => $country_short,
			'postcode' => $postcode,
			'formattedAddress' => $formattedAddress,
		];
	} else {
		echo 'Geocoding API error: ' . $data['status'];
		return null;
	}
}

function base64ToImage($base64,$partyid) {
	// Replace $base64_string with your actual Base64 encoded string
	$base64_string = $base64; // Example Base64 string

	// Extract the data part of the Base64 string (excluding data:image/png;base64,)
	$data = explode(',', $base64_string);
	$base64_data = isset($data[1]) ? $data[1] : '';

	// Decode the Base64 data into binary
	$image_data = base64_decode($base64_data);

	// Generate a unique filename for the image
	$filename = "uploads/{$partyid_}".uniqid('image_') . '.png';

	// Save the image to a file
	file_put_contents($filename, $image_data);  
	
	return $filename;
}

function convertDateFormatWithoutDateTime($inputDate) {
	// Split the input date into day, month, and year
	$dateParts = explode('/', $inputDate);

	// Check if the array has three elements (day, month, and year)
	if (count($dateParts) !== 3) {
		return 'Invalid date format';
	}

	// Rearrange the date parts into the desired format
	$outputDate = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];

	return $outputDate;
} 

function extractBasicAuthCredentials() {
    $authorizationHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

    if (strpos($authorizationHeader, 'Basic ') === 0) {
        $base64Credentials = substr($authorizationHeader, 6);
        $credentials = base64_decode($base64Credentials);

        if ($credentials !== false) {
            list($username, $password) = explode(':', $credentials, 2);
            return ['username' => $username, 'password' => $password];
        }
    }

    return null;
}

function isDocumentUrl($url) {
    $headers = get_headers($url, 1);

    if ($headers && isset($headers['Content-Type'])) {
        $contentType = $headers['Content-Type'];

        // Check if the content type indicates a document (e.g., PDF, Word, etc.)
        $documentContentTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

        foreach ($documentContentTypes as $documentType) {
            if (strpos($contentType, $documentType) !== false) {
                return true; // It's a document
            }
        }
    }

    return false; // It's not a document or headers not available
}

function isUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// function diddit_fetchClientToken($clientID, $clientSecret) {
//     $url = 'https://apx.didit.me/auth/v2/token/';
//     // $clientID = getenv('NEXT_PUBLIC_DIDIT_CLIENT_ID');
//     // $clientSecret = getenv('CLIENT_SECRET');

//     $encodedCredentials = base64_encode("$clientID:$clientSecret");

//     $params = http_build_query([
//         'grant_type' => 'client_credentials'
//     ]);

//     $headers = [
//         "Authorization: Basic $encodedCredentials",
//         "Content-Type: application/x-www-form-urlencoded"
//     ];

//     $ch = curl_init($url);

//     curl_setopt($ch, CURLOPT_POST, true);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

//     $response = curl_exec($ch);
//     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

//     if (curl_errno($ch)) {
//         error_log('Network error: ' . curl_error($ch));
//         curl_close($ch);
//         return null;
//     }

//     curl_close($ch);
//     $data = json_decode($response, true);

//     if ($httpCode >= 200 && $httpCode < 300) {
//         return $data;
//     } else {
//         error_log('Error fetching client token: ' . ($data['message'] ?? 'Unknown error'));
//         return null;
//     }
// }

// function diddit_createSession($accessToken,$features, $callback, $vendor_data) {
//     $url = 'https://verification.didit.me/v1/session/';
    
//     //$accessToken = $tokenData['access_token'];

//     $body = json_encode([
//         'vendor_data' => $vendor_data,
//         'callback' => $callback,
//         'features' => $features
//     ]);

//     $headers = [
//         "Content-Type: application/json",
//         "Authorization: Bearer $accessToken"
//     ];;

//     $ch = curl_init($url);

//     curl_setopt($ch, CURLOPT_POST, true);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

//     $response = curl_exec($ch);
//     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

//     if (curl_errno($ch)) {
//         error_log('Network error: ' . curl_error($ch));
//         curl_close($ch);
//         return null;
//     }

//     curl_close($ch);
//     $data = json_decode($response, true);

// 	if ($httpCode === 201 && $data) {
// 		return $data;
// 	} else {
// 		// Nuevo log detallado
// 		error_log("HTTP CODE: $httpCode");
// 		error_log("RAW RESPONSE: $response");

// 		$errorMessage = is_array($data) && isset($data['message']) ? $data['message'] : 'Unexpected error or invalid JSON response';
// 		throw new Exception($errorMessage);
// 	}
// }

function didit_createSession_v2($apiKey, $workflow_id, $callback, $metadata, $language = null, $vendor_data, $contact_details = null, $expected_details = null) {
    $url = 'https://verification.didit.me/v2/session/';
  
	$bodyArr = array();
    if ($language !== null) {
        $bodyArr['language'] = $language;
    }

    if ($metadata !== null) {
        $bodyArr['metadata'] = $metadata;
    }

    if ($vendor_data !== null) {
        $bodyArr['vendor_data'] = $vendor_data;
    }	

    if ($contact_details !== null) {
        $bodyArr['contact_details'] = $contact_details;
    }	

    if ($expected_details !== null) {
        $bodyArr['expected_details'] = $expected_details;
    }	

    $bodyArr['workflow_id'] = $workflow_id;
    $bodyArr['callback'] = $callback;

    $bodyJson = json_encode($bodyArr);

	// print_r($bodyJson);
	// die();	

    $headers = [
		"accept: application/json",
        "Content-Type: application/json",
        "X-Api-Key: $apiKey",
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log('Network error: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }

    curl_close($ch);
    $response = json_decode($response, true);
	//$response["language"] = $bodyArr['language'];

	if (isset($response["metadata"])){
		unset($response["metadata"]);
	}
	if (isset($response["vendor_data"])){
		unset($response["vendor_data"]);
	}
	if (isset($response["vendor_data"])){
		unset($response["vendor_data"]);
	}

    if ($httpCode === 201 && $response) {
        return $response;
    } else {
        error_log("HTTP CODE: $httpCode");
        //error_log("RAW RESPONSE: $response");
        $errorMessage = is_array($response) && isset($response['message']) ? $response['message'] : 'Unexpected error or invalid JSON response';
        //print_r($errorMessage);
		throw new Exception($errorMessage);
    }
}

function didit_getDecision($apiKey, $session_id) {
    $url = "https://verification.didit.me/v2/session/" . urlencode($session_id) . "/decision/";

    $headers = [
        "Content-Type: application/json",
        "X-Api-Key: $apiKey"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception("Network error: " . curl_error($ch));
    }

    curl_close($ch);

    $data = json_decode($response, true);

	unset($data["vendor_data"], $data["callback"], $data["aml"]);

    if ($httpCode === 200 && $data) {
        return $data;
    } else {
        $errorMsg = isset($data['message']) ? $data['message'] : $response;
        throw new Exception("HTTP $httpCode - $errorMsg");
    }
}

function sendResponse($status, $code, $message, $data = null, $observations = null) {
    http_response_code($code);
    $response = [
        'status'    => $status,
        'code'      => $code,
        'message'   => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    if ($observations !== null && !empty($observations)) {
        $response['observations'] = $observations;
    }
    echo json_encode($response);
    exit();
}

function alpha3ToAlpha2($alpha3) {
	if ($alpha3 <> null) {
		$map = [
			'ABW' => 'AW', 'AFG' => 'AF', 'AGO' => 'AO', 'AIA' => 'AI', 'ALA' => 'AX',
			'ALB' => 'AL', 'AND' => 'AD', 'ARE' => 'AE', 'ARG' => 'AR', 'ARM' => 'AM',
			'ASM' => 'AS', 'ATA' => 'AQ', 'ATF' => 'TF', 'ATG' => 'AG', 'AUS' => 'AU',
			'AUT' => 'AT', 'AZE' => 'AZ', 'BDI' => 'BI', 'BEL' => 'BE', 'BEN' => 'BJ',
			'BES' => 'BQ', 'BFA' => 'BF', 'BGD' => 'BD', 'BGR' => 'BG', 'BHR' => 'BH',
			'BHS' => 'BS', 'BIH' => 'BA', 'BLM' => 'BL', 'BLR' => 'BY', 'BLZ' => 'BZ',
			'BMU' => 'BM', 'BOL' => 'BO', 'BRA' => 'BR', 'BRB' => 'BB', 'BRN' => 'BN',
			'BTN' => 'BT', 'BVT' => 'BV', 'BWA' => 'BW', 'CAF' => 'CF', 'CAN' => 'CA',
			'CCK' => 'CC', 'CHE' => 'CH', 'CHL' => 'CL', 'CHN' => 'CN', 'CIV' => 'CI',
			'CMR' => 'CM', 'COD' => 'CD', 'COG' => 'CG', 'COK' => 'CK', 'COL' => 'CO',
			'COM' => 'KM', 'CPV' => 'CV', 'CRI' => 'CR', 'CUB' => 'CU', 'CUW' => 'CW',
			'CXR' => 'CX', 'CYM' => 'KY', 'CYP' => 'CY', 'CZE' => 'CZ', 'DEU' => 'DE',
			'DJI' => 'DJ', 'DMA' => 'DM', 'DNK' => 'DK', 'DOM' => 'DO', 'DZA' => 'DZ',
			'ECU' => 'EC', 'EGY' => 'EG', 'ERI' => 'ER', 'ESH' => 'EH', 'ESP' => 'ES',
			'EST' => 'EE', 'ETH' => 'ET', 'FIN' => 'FI', 'FJI' => 'FJ', 'FLK' => 'FK',
			'FRA' => 'FR', 'FRO' => 'FO', 'FSM' => 'FM', 'GAB' => 'GA', 'GBR' => 'GB',
			'GEO' => 'GE', 'GGY' => 'GG', 'GHA' => 'GH', 'GIB' => 'GI', 'GIN' => 'GN',
			'GLP' => 'GP', 'GMB' => 'GM', 'GNB' => 'GW', 'GNQ' => 'GQ', 'GRC' => 'GR',
			'GRD' => 'GD', 'GRL' => 'GL', 'GTM' => 'GT', 'GUF' => 'GF', 'GUM' => 'GU',
			'GUY' => 'GY', 'HKG' => 'HK', 'HMD' => 'HM', 'HND' => 'HN', 'HRV' => 'HR',
			'HTI' => 'HT', 'HUN' => 'HU', 'IDN' => 'ID', 'IMN' => 'IM', 'IND' => 'IN',
			'IOT' => 'IO', 'IRL' => 'IE', 'IRN' => 'IR', 'IRQ' => 'IQ', 'ISL' => 'IS',
			'ISR' => 'IL', 'ITA' => 'IT', 'JAM' => 'JM', 'JEY' => 'JE', 'JOR' => 'JO',
			'JPN' => 'JP', 'KAZ' => 'KZ', 'KEN' => 'KE', 'KGZ' => 'KG', 'KHM' => 'KH',
			'KIR' => 'KI', 'KNA' => 'KN', 'KOR' => 'KR', 'KWT' => 'KW', 'LAO' => 'LA',
			'LBN' => 'LB', 'LBR' => 'LR', 'LBY' => 'LY', 'LCA' => 'LC', 'LIE' => 'LI',
			'LKA' => 'LK', 'LSO' => 'LS', 'LTU' => 'LT', 'LUX' => 'LU', 'LVA' => 'LV',
			'MAC' => 'MO', 'MAF' => 'MF', 'MAR' => 'MA', 'MCO' => 'MC', 'MDA' => 'MD',
			'MDG' => 'MG', 'MDV' => 'MV', 'MEX' => 'MX', 'MHL' => 'MH', 'MKD' => 'MK',
			'MLI' => 'ML', 'MLT' => 'MT', 'MMR' => 'MM', 'MNE' => 'ME', 'MNG' => 'MN',
			'MNP' => 'MP', 'MOZ' => 'MZ', 'MRT' => 'MR', 'MSR' => 'MS', 'MTQ' => 'MQ',
			'MUS' => 'MU', 'MWI' => 'MW', 'MYS' => 'MY', 'MYT' => 'YT', 'NAM' => 'NA',
			'NCL' => 'NC', 'NER' => 'NE', 'NFK' => 'NF', 'NGA' => 'NG', 'NIC' => 'NI',
			'NIU' => 'NU', 'NLD' => 'NL', 'NOR' => 'NO', 'NPL' => 'NP', 'NRU' => 'NR',
			'NZL' => 'NZ', 'OMN' => 'OM', 'PAK' => 'PK', 'PAN' => 'PA', 'PCN' => 'PN',
			'PER' => 'PE', 'PHL' => 'PH', 'PLW' => 'PW', 'PNG' => 'PG', 'POL' => 'PL',
			'PRI' => 'PR', 'PRK' => 'KP', 'PRT' => 'PT', 'PRY' => 'PY', 'PSE' => 'PS',
			'PYF' => 'PF', 'QAT' => 'QA', 'REU' => 'RE', 'ROU' => 'RO', 'RUS' => 'RU',
			'RWA' => 'RW', 'SAU' => 'SA', 'SDN' => 'SD', 'SEN' => 'SN', 'SGP' => 'SG',
			'SGS' => 'GS', 'SHN' => 'SH', 'SJM' => 'SJ', 'SLB' => 'SB', 'SLE' => 'SL',
			'SLV' => 'SV', 'SMR' => 'SM', 'SOM' => 'SO', 'SPM' => 'PM', 'SRB' => 'RS',
			'SSD' => 'SS', 'STP' => 'ST', 'SUR' => 'SR', 'SVK' => 'SK', 'SVN' => 'SI',
			'SWE' => 'SE', 'SWZ' => 'SZ', 'SXM' => 'SX', 'SYC' => 'SC', 'SYR' => 'SY',
			'TCA' => 'TC', 'TCD' => 'TD', 'TGO' => 'TG', 'THA' => 'TH', 'TJK' => 'TJ',
			'TKL' => 'TK', 'TKM' => 'TM', 'TLS' => 'TL', 'TON' => 'TO', 'TTO' => 'TT',
			'TUN' => 'TN', 'TUR' => 'TR', 'TUV' => 'TV', 'TWN' => 'TW', 'TZA' => 'TZ',
			'UGA' => 'UG', 'UKR' => 'UA', 'UMI' => 'UM', 'URY' => 'UY', 'USA' => 'US',
			'UZB' => 'UZ', 'VAT' => 'VA', 'VCT' => 'VC', 'VEN' => 'VE', 'VGB' => 'VG',
			'VIR' => 'VI', 'VNM' => 'VN', 'VUT' => 'VU', 'WLF' => 'WF', 'WSM' => 'WS',
			'YEM' => 'YE', 'ZAF' => 'ZA', 'ZMB' => 'ZM', 'ZWE' => 'ZW'
		];

		$alpha3 = strtoupper($alpha3);
	}
    return $map[$alpha3] ?? null;

}

function removeEmptyValues(array $data): array {
    return array_filter($data, function ($value) {
        // Remove null, empty string, or whitespace-only strings
        return !(is_null($value) || $value === '' || (is_string($value) && trim($value) === ''));
    });
}

function alpha2ToAlpha3($alpha2)
{
    if ($alpha2 !== null) {
        $map = [
            'AW' => 'ABW', 'AF' => 'AFG', 'AO' => 'AGO', 'AI' => 'AIA', 'AX' => 'ALA',
            'AL' => 'ALB', 'AD' => 'AND', 'AE' => 'ARE', 'AR' => 'ARG', 'AM' => 'ARM',
            'AS' => 'ASM', 'AQ' => 'ATA', 'TF' => 'ATF', 'AG' => 'ATG', 'AU' => 'AUS',
            'AT' => 'AUT', 'AZ' => 'AZE', 'BI' => 'BDI', 'BE' => 'BEL', 'BJ' => 'BEN',
            'BQ' => 'BES', 'BF' => 'BFA', 'BD' => 'BGD', 'BG' => 'BGR', 'BH' => 'BHR',
            'BS' => 'BHS', 'BA' => 'BIH', 'BL' => 'BLM', 'BY' => 'BLR', 'BZ' => 'BLZ',
            'BM' => 'BMU', 'BO' => 'BOL', 'BR' => 'BRA', 'BB' => 'BRB', 'BN' => 'BRN',
            'BT' => 'BTN', 'BV' => 'BVT', 'BW' => 'BWA', 'CF' => 'CAF', 'CA' => 'CAN',
            'CC' => 'CCK', 'CH' => 'CHE', 'CL' => 'CHL', 'CN' => 'CHN', 'CI' => 'CIV',
            'CM' => 'CMR', 'CD' => 'COD', 'CG' => 'COG', 'CK' => 'COK', 'CO' => 'COL',
            'KM' => 'COM', 'CV' => 'CPV', 'CR' => 'CRI', 'CU' => 'CUB', 'CW' => 'CUW',
            'CX' => 'CXR', 'KY' => 'CYM', 'CY' => 'CYP', 'CZ' => 'CZE', 'DE' => 'DEU',
            'DJ' => 'DJI', 'DM' => 'DMA', 'DK' => 'DNK', 'DO' => 'DOM', 'DZ' => 'DZA',
            'EC' => 'ECU', 'EG' => 'EGY', 'ER' => 'ERI', 'EH' => 'ESH', 'ES' => 'ESP',
            'EE' => 'EST', 'ET' => 'ETH', 'FI' => 'FIN', 'FJ' => 'FJI', 'FK' => 'FLK',
            'FR' => 'FRA', 'FO' => 'FRO', 'FM' => 'FSM', 'GA' => 'GAB', 'GB' => 'GBR',
            'GE' => 'GEO', 'GG' => 'GGY', 'GH' => 'GHA', 'GI' => 'GIB', 'GN' => 'GIN',
            'GP' => 'GLP', 'GM' => 'GMB', 'GW' => 'GNB', 'GQ' => 'GNQ', 'GR' => 'GRC',
            'GD' => 'GRD', 'GL' => 'GRL', 'GT' => 'GTM', 'GF' => 'GUF', 'GU' => 'GUM',
            'GY' => 'GUY', 'HK' => 'HKG', 'HM' => 'HMD', 'HN' => 'HND', 'HR' => 'HRV',
            'HT' => 'HTI', 'HU' => 'HUN', 'ID' => 'IDN', 'IM' => 'IMN', 'IN' => 'IND',
            'IO' => 'IOT', 'IE' => 'IRL', 'IR' => 'IRN', 'IQ' => 'IRQ', 'IS' => 'ISL',
            'IL' => 'ISR', 'IT' => 'ITA', 'JM' => 'JAM', 'JE' => 'JEY', 'JO' => 'JOR',
            'JP' => 'JPN', 'KZ' => 'KAZ', 'KE' => 'KEN', 'KG' => 'KGZ', 'KH' => 'KHM',
            'KI' => 'KIR', 'KN' => 'KNA', 'KR' => 'KOR', 'KW' => 'KWT', 'LA' => 'LAO',
            'LB' => 'LBN', 'LR' => 'LBR', 'LY' => 'LBY', 'LC' => 'LCA', 'LI' => 'LIE',
            'LK' => 'LKA', 'LS' => 'LSO', 'LT' => 'LTU', 'LU' => 'LUX', 'LV' => 'LVA',
            'MO' => 'MAC', 'MF' => 'MAF', 'MA' => 'MAR', 'MC' => 'MCO', 'MD' => 'MDA',
            'MG' => 'MDG', 'MV' => 'MDV', 'MX' => 'MEX', 'MH' => 'MHL', 'MK' => 'MKD',
            'ML' => 'MLI', 'MT' => 'MLT', 'MM' => 'MMR', 'ME' => 'MNE', 'MN' => 'MNG',
            'MP' => 'MNP', 'MZ' => 'MOZ', 'MR' => 'MRT', 'MS' => 'MSR', 'MQ' => 'MTQ',
            'MU' => 'MUS', 'MW' => 'MWI', 'MY' => 'MYS', 'YT' => 'MYT', 'NA' => 'NAM',
            'NC' => 'NCL', 'NE' => 'NER', 'NF' => 'NFK', 'NG' => 'NGA', 'NI' => 'NIC',
            'NU' => 'NIU', 'NL' => 'NLD', 'NO' => 'NOR', 'NP' => 'NPL', 'NR' => 'NRU',
            'NZ' => 'NZL', 'OM' => 'OMN', 'PK' => 'PAK', 'PA' => 'PAN', 'PN' => 'PCN',
            'PE' => 'PER', 'PH' => 'PHL', 'PW' => 'PLW', 'PG' => 'PNG', 'PL' => 'POL',
            'PR' => 'PRI', 'KP' => 'PRK', 'PT' => 'PRT', 'PY' => 'PRY', 'PS' => 'PSE',
            'PF' => 'PYF', 'QA' => 'QAT', 'RE' => 'REU', 'RO' => 'ROU', 'RU' => 'RUS',
            'RW' => 'RWA', 'SA' => 'SAU', 'SD' => 'SDN', 'SN' => 'SEN', 'SG' => 'SGP',
            'GS' => 'SGS', 'SH' => 'SHN', 'SJ' => 'SJM', 'SB' => 'SLB', 'SL' => 'SLE',
            'SV' => 'SLV', 'SM' => 'SMR', 'SO' => 'SOM', 'PM' => 'SPM', 'RS' => 'SRB',
            'SS' => 'SSD', 'ST' => 'STP', 'SR' => 'SUR', 'SK' => 'SVK', 'SI' => 'SVN',
            'SE' => 'SWE', 'SZ' => 'SWZ', 'SX' => 'SXM', 'SC' => 'SYC', 'SY' => 'SYR',
            'TC' => 'TCA', 'TD' => 'TCD', 'TG' => 'TGO', 'TH' => 'THA', 'TJ' => 'TJK',
            'TK' => 'TKL', 'TM' => 'TKM', 'TL' => 'TLS', 'TO' => 'TON', 'TT' => 'TTO',
            'TN' => 'TUN', 'TR' => 'TUR', 'TV' => 'TUV', 'TW' => 'TWN', 'TZ' => 'TZA',
            'UG' => 'UGA', 'UA' => 'UKR', 'UM' => 'UMI', 'UY' => 'URY', 'US' => 'USA',
            'UZ' => 'UZB', 'VA' => 'VAT', 'VC' => 'VCT', 'VE' => 'VEN', 'VG' => 'VGB',
            'VI' => 'VIR', 'VN' => 'VNM', 'VU' => 'VUT', 'WF' => 'WLF', 'WS' => 'WSM',
            'YE' => 'YEM', 'ZA' => 'ZAF', 'ZM' => 'ZMB', 'ZW' => 'ZWE'
        ];

        $alpha2 = strtoupper($alpha2);
        return $map[$alpha2] ?? null;
    }

    return null;
}

function getPartyByIdOrExternal($conn, $party_id = null, $external_id = null) {
    if (!$party_id && !$external_id) {
        return [
            "error" => "party_id or external_id is required"
        ];
    }

    $tableName = "party";
    $columns = "*"; 
    $filters = [];

    if ($party_id) {
        $filters['party_id'] = $party_id;
    }

    if ($external_id) {
        $filters['external_id'] = $external_id;
    }

    // SELECT * FROM party WHERE party_id = X OR external_id = 'Y'
    $result = selectData($conn, $tableName, $columns, $filters, "")['data'];

    if (empty($result)) {
        return [
            "error" => "Party not found with given IDs"
        ];
    }

    return $result[0]; // Return single row
}

function call_tree_evaluation ($payload,$Client_ID,$Secret_Key) {
	$tree_evaluation_url = "http://localhost:8080/api/v1/tree_evaluation";
	$tree_evaluation_payload = json_encode($payload);

	$ch = curl_init($tree_evaluation_url);
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $tree_evaluation_payload,
		CURLOPT_HTTPHEADER => [
			'Content-Type: application/json',
			'Authorization: Basic ' . base64_encode("$Client_ID:$Secret_Key")
		],
		CURLOPT_TIMEOUT => 60
	]);

	$response_tree_evaluation = curl_exec($ch);
	$http_code_tree_evaluation = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($http_code_tree_evaluation !== 200) {
        return [
            "success"      => false,
            "http_code"    => $http_code_tree_evaluation,
            "error_type"   => "curl_error",
            "error_msg"    => $response_tree_evaluation,
            "raw_response" => null
        ];
	}

	
	$tree_evaluation_response_data = json_decode($response_tree_evaluation, true)['data'] ?? null;
	if ($tree_evaluation_response_data) {
		return [
			"success"      => true,
			"http_code"    => $http_code_tree_evaluation,
			"data"         => $tree_evaluation_response_data,
			"message"      => "Tree Evaluation Success",
		];
	}	
}

function curl_async_post($url, $payload, $Client_ID, $Secret_Key)
{
    $base = "http://localhost:8080/";
    $url  = rtrim($base, "/") . "/" . ltrim($url, "/");

    $payload = json_encode($payload);
    $auth = base64_encode("$Client_ID:$Secret_Key");

    $cmd = "curl -X POST '$url' ".
           "-H 'Content-Type: application/json' ".
           "-H 'Authorization: Basic $auth' ".
           "-d '$payload' ".
           "> /dev/null 2>&1 &";

    // Ejecutar sin bloquear
    proc_open($cmd, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ], $pipes);

    return [
        "success" => true,
        "message" => "Async request launched"
    ];
}


function log_Failure($conn, $log_id, $time_start, $inputData,$output_string) {
    // Extract error output
    $update_fields = [
        "exec_time" => round(microtime(true) - $time_start, 2),
        "status"    => 'failed',
        "output"    => json_encode($output_string)
    ];

    if (!empty($inputData['party_id'])) {
        $update_fields["party_id"] = intval($inputData['party_id']);
    }

    update_data($conn, "party_onboarding_log", $update_fields, [
        "ID = {$log_id}",
    ]);
}

function curlRequest_v2($url, $method = 'GET', $data = "", $headers = [], $username = null, $password = null) {

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 20, // evita que quede colgado
    ]);

    if (($method === 'POST' || $method === 'PUT') && !empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    if ($username && $password) {
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // ------ ERROR DE CURL ------
    if (curl_errno($ch)) {
        $errorMsg = curl_error($ch);
        curl_close($ch);

        return [
            "success" => false,
            "error_type" => "curl_error",
            "error_message" => $errorMsg,
            "http_code" => 0,
            "response" => null
        ];
    }

    curl_close($ch);

    // ------ ERROR HTTP ------
    if ($httpCode < 200 || $httpCode >= 300) {
        return [
            "success" => false,
            "error_type" => "http_error",
            "error_message" => "HTTP $httpCode returned by server",
            "http_code" => $httpCode,
            "response" => $response
        ];
    }

    // ------ INTENTAR DECODEAR JSON ------
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            "success" => false,
            "error_type" => "json_error",
            "error_message" => "Invalid JSON returned: " . json_last_error_msg(),
            "http_code" => $httpCode,
            "response" => $response
        ];
    }

    // ------ TODO OK ------
    return [
        "success" => true,
        "http_code" => $httpCode,
        "response" => $decoded
    ];
}

use Google\Cloud\PubSub\PubSubClient;

function publish_event($topicName, array $data)
{
    $projectId = getenv('GOOGLE_PROJECT_ID');

    try {
        $pubsub = new PubSubClient([
            'projectId' => $projectId,
        ]);

        $topic = $pubsub->topic($topicName);

        // Publicar como JSON
        $jsonPayload = json_encode($data, JSON_UNESCAPED_UNICODE);

        $result = $topic->publish([
            'data' => $jsonPayload
        ]);

        return [
            "success" => true,
            "messageId" => $result['messageIds'][0] ?? null
        ];

    } catch (Throwable $e) {
        return [
            "success" => false,
            "error" => $e->getMessage()
        ];
    }
}



?>