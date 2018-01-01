<?php
// webhook adress
// edit discord channel -> webhooks -> edit or create webhook -> webhook url
$webhookUrl = "https://discordapp.com/api/webhooks/..../...";

if (in_array(array("34.198.203.127", "34.198.178.64", "34.198.32.85"), $_SERVER['REMOTE_ADDR']))
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