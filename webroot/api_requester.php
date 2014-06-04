<?php session_start(); ?>
<body style="width:90%;margin:20px auto;"><?php 

if (isset($_GET['submit']) && !empty($_POST)) {
    
	$url = $_POST['url_base'] . $_POST['url'];
    
    $data = $_POST['data'];
    $data = '{' . str_replace("'", '"', $data) . '}';
    $fields = json_decode($data, true);

    if (!isset($fields['debug'])) {
    	$fields['debug'] = 2;
	}
    
    //file upload
    if (isset($_FILES['file_file']['tmp_name']) && !empty($_FILES['file_file']['tmp_name'])) {
    	$pathinfo = pathinfo($_FILES['file_file']['tmp_name']);
        $new_file = str_replace('\\', '/', $pathinfo['dirname']) . '/' . $_FILES['file_file']['name'];
        move_uploaded_file($_FILES['file_file']['tmp_name'], $new_file);
        $fields[ $_POST['file_name'] ] = '@' . $new_file;
    }

    if (!empty($_POST['hash_key']) && !empty($_POST['hash_var'])) {
    	$fields_hash = $fields;
    	ksort($fields_hash);
    	if (!empty($_POST['hash_exclude'])) {
    		$exclude = explode(',', $_POST['hash_exclude']);
    	}
    	foreach($exclude as $item) {
    		unset($fields_hash[$item]);
    	}
    	//echo '<p>'.implode($_POST['hash_cat'], $fields_hash) .' xxx ';
    	//echo '<p>'.$_POST['hash_key'];
    	$fields[ $_POST['hash_var'] ] = hash_hmac('sha512', implode($_POST['hash_cat'], $fields_hash), $_POST['hash_key']);
    }
      
    //$fields['name'] .= '༂';// '܀';
    
    //$fields_string = http_build_query($fields);
	
	//open connection
	$ch = curl_init();
	
	//$ckfile = tempnam ("./", "CURLCOOKIE");
	//var_dump($ckfile);
	//$ckfile = 'C:/m/work/www/bawte_request/CUR962A.tmp';
	
	//set the url, number of POST vars, POST data
	//curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: multipart/form-data"));
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST,count($fields));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	if (isset($fields[ $_POST['file_name'] ])) {
		curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
	} else {
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($fields));
	}
	
	//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ; 
	//curl_setopt($ch, CURLOPT_USERPWD, "login:pwd");

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	//return the transfer as a string
	//curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
	//curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile); 
	$result = curl_exec($ch);	//execute post
	
	$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	$error = curl_error($ch);
	if (!empty($error)) {
		echo '<p><b>cURL error:</b> ' . $error . '</p>';
	}
	curl_close($ch);	//close connection
	
	if (isset($new_file)) {
		unlink($new_file);
	}
	
	if (isset($_POST['file_name']) && isset($fields[ $_POST['file_name'] ])) {
		echo '<i>Uploading file '.$_FILES['file_file']['name'].'</i><br />';
	}
	if (isset($_POST['hash_var']) && !empty($_POST['hash_var']) && isset($fields[ $_POST['hash_var'] ]) && !empty($fields[ $_POST['hash_var'] ])) {
		echo '<p>We sent the following fingerprint: <code>' . $fields[ $_POST['hash_var'] ] . '</code></p>';
	}
	echo '<b>Response from '.$url.'</b>: <a href="#" onclick="document.getElementById(\'request_form\').submit();return false;">[refresh]</a>';
	//echo '<br />HTTP Code: '.$http_status.'<br />';
	echo '<pre style="border:3px #aaa solid; padding:20px; background:#ffc;">';
	print_r($result);
	echo '</pre>';
    
    if (isset($_POST['auth'])) {
        $result_array = json_decode($result, true);
        //print_r($result_array['data']['api_token']);
        $_POST['data'] = '"member_big":'.$result_array['data']['member_big'].",\n".
                         '"api_token":"'.$result_array['data']['api_token']."\"\n";
        $_POST['url'] = '';
        $_POST['file_name'] = '';
    }
    $_SESSION['form_data'] = $_POST;
	
} elseif (isset($_SESSION['form_data']) && !empty($_SESSION['form_data'])) {
	$_POST = $_SESSION['form_data'];
} else {
	$_POST = array(
		'url' => 'http://http://54.228.195.174/api/', 
		'url_base' => '', 
		'data' => '', 
		'file_name' => '', 
		'hash_key' => '', 
		'hash_var' => '', 
		'hash_exclude' => 'debug', 
		'hash_cat' => ''
	);
}

?>

<form action="?submit" method="post" id="request_form" enctype="multipart/form-data">

<b>Request form:</b>

<p><input type="text" size="30" value="<?php echo $_POST['url_base']; ?>" name="url_base" /> <input type="text" size="40" value="<?php echo $_POST['url']; ?>" name="url" />

<p><textarea name="data" rows="10" cols="100" style="width:100%;"><?php echo $_POST['data']; ?></textarea>

<p>Specify file variable name and select file for upload<br />
<input type="text" name="file_name" size="18" value="<?php echo $_POST['file_name']; ?>" /> : <input type="file" name="file_file" />

<p><a href="#" onclick="document.getElementById('hash_setup').style.display='block';return false;">Calculate HMAC hash (sha512) from all variables</a>
<p id="hash_setup" style="display:none;">
Hash key: <input type="text" name="hash_key" size="30" value="<?php echo $_POST['hash_key']; ?>" /><br />
Send hash in variable: <input type="text" name="hash_var" size="30" value="<?php echo $_POST['hash_var']; ?>" /><br />
Exclude from hash: <input type="text" name="hash_exclude" size="30" value="<?php echo $_POST['hash_exclude']; ?>" /><br />
Concat fields with string: <input type="text" name="hash_cat" size="30" value="<?php echo $_POST['hash_cat']; ?>" /><br />
</p>


<p><input type="submit" value="request" />

</form>

</body>