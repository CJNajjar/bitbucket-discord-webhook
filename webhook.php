<?php
// webhook adress
// edit discord channel -> webhooks -> edit or create webhook -> webhook url
$webhookUrl = "https://discordapp.com/api/webhooks/..../...";

// function source: https://gist.github.com/tott/7684443
function ip_in_range( $ip, $range )
{
	if ( strpos( $range, '/' ) == false )
	{
		$range .= '/32';
	}
	// $range is in IP/CIDR format eg 127.0.0.1/24
	list( $range, $netmask ) = explode( '/', $range, 2 );
	$range_decimal = ip2long( $range );
	$ip_decimal = ip2long( $ip );
	$wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
	$netmask_decimal = ~ $wildcard_decimal;
	return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}

if (!in_array($_SERVER['REMOTE_ADDR'], array("34.198.203.127", "34.198.178.64", "34.198.32.85")) && !ip_in_range($_SERVER['REMOTE_ADDR'], "104.192.136.0/21"))
{
	die();
}

$requestBody = file_get_contents('php://input');
$webhookArray = json_decode($requestBody, true);

if (!isset($webhookArray["push"]))
{
	die();
}

$username = $webhookArray["actor"]["username"];
$userUrl = $webhookArray["actor"]["links"]["html"]["href"];
$avatarUrl = $webhookArray["actor"]["links"]["avatar"]["href"];

$repositoryName = $webhookArray["repository"]["name"] . "." . $webhookArray["repository"]["scm"];

$branch = $webhookArray["push"]["changes"][0]["new"]["name"];

$commits = array();

foreach ($webhookArray["push"]["changes"][0]["commits"] as $commitArr)
{
	$hash = substr($commitArr["hash"], 0, 7);
	$commitMessage = $commitArr["message"];
	
	$commits[] = [
		"name" => $hash . " (" . date("Y-m-d H:i:s", strtotime($commitArr["date"])) . ")",
		"value" => $commitMessage
	];
}

$commits = array_reverse($commits);

$fields = [
	'embeds' => [
		[
			"description" => "pushte soeben in GIT-Repository \"".$repositoryName."\" (Branch: ".$branch.")\n",
			"fields" => $commits,
			"author" => [
				"name" => $username,
				"url" => $userUrl,
				"icon_url" => $avatarUrl
			]
		]
	]
];

$ch = curl_init();

//set the url, number of POST vars, POST data
curl_setopt($ch,CURLOPT_URL, $webhookUrl);
curl_setopt($ch,CURLOPT_POST, count($fields));
curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($fields));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'Content-Type: application/json'
    ));

//execute post
$result = curl_exec($ch);

//close connection
curl_close($ch);