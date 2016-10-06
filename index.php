<?php

# Google Site Verification Bulk Changer

require_once 'google-api-php-client/vendor/autoload.php';
include_once 'google-api-php-client/examples/templates/base.php';
include 'config.php';

echo pageHeader("Google Site Owner Bulk Change");

# ensure oauth credentials are downloaded

if (!$oauth_credentials) {
	echo "Missing config file or oauth credentials";
	die();
}

/*if (!$oauth_credentials = getOAuthCredentialsFile()) {
  echo missingOAuth2CredentialsWarning();
  return;
}*/

# set redirect URI for same script

$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

$client = new Google_Client();
$client->setAuthConfig($oauth_credentials);
$client->setRedirectUri($redirect_uri);
$client->addScope("https://www.googleapis.com/auth/siteverification");

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);
	  // store in the session also
	$_SESSION['upload_token'] = $token;
	header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}

// set the access token as part of the client
if (!empty($_SESSION['upload_token'])) {
  $client->setAccessToken($_SESSION['upload_token']);
  if ($client->isAccessTokenExpired()) {
    unset($_SESSION['upload_token']);
  }
} else {
  $authUrl = $client->createAuthUrl();
}

/************************************************
 * If we're signed in then lets try to do the job
  ************************************************/
 
 $service = new Google_Service_SiteVerification($client);
 
if ($client->getAccessToken())
{
	
	if ($_POST['email_address'])
	{
		
		# assign rights to new user
		$siteArray = getSiteList($service);
		$email_address = $_POST['email_address'];
		
		foreach ($siteArray as $site)
		{
			$service = new Google_Service_SiteVerification($client);
			setSitePermission($service, $site, $email_address);
		}
		
		
	}
	
	# if we're authenticated
	echo "You're authenticated<br>";
	
	echo "<table>";
	foreach (getSiteList($service) as $site)
	{
		echo "<tr><td>";
		echo urldecode($site->id);
		echo "</td></tr>";
	}
	echo "</table>";
	
	
}
 
function getSiteList($service)
{
	# get list of authenticated sites
	$siteList = $service->webResource->listWebResource();
	
	$siteArray = array();
	
	foreach ($siteList->getItems() as $siteItem)
	{
		#array_push($siteArray, $siteItem->id);
		array_push($siteArray, $siteItem);
	}
		
	#print_r($siteArray)	;
	return($siteArray);
}

function setSitePermission($service, $site, $email_address)
{
	# set the permission for a site
	
	#echo "ID: ".urldecode($site->id)."<br>";
	#echo "Owners: ";
	#print_r($site->getOwners());
	$owners = $site->getOwners();
	array_push($owners, $email_address);
	#print_r($site->owners);
	#echo "<br>";
	#array_push($site->owners, $email_address);
	#echo "New Owners:";
	#print_r($owners);
	$site->setOwners($owners);
	#echo "<br>";
	#echo "Site:";
	#print_r($site);
	#echo "<br>";
		
	#$newsite = new Google_Service_SiteVerification_SiteVerificationWebResourceResource($site);
	#$newsite->setId($site->id);
	#$newsite->setOwners($site->owners);
	
	#echo "Newsite";
	#print_r($newsite);
	#echo "<br>";
	
	
	/*
	$site = new Google_Service_SiteVerification_SiteVerificationWebResourceResourceSite();
	$site->setIdentifier($site->id);
	
	$request = new Google_Service_SiteVerification_SiteVerificationWebResourceResource();
	$request->setSite($site);
	
	$webResource = $service->webResource;
	#$result = $webResource->insert('FILE',$request);
	$result = $webResource->update($site->id,$request);
	
	echo "Result:";
	print_t($result);
	echo "<br>";*/
	#echo "Response:<br>";
	
	if ($response = $service->webResource->update(urldecode($site->id), $site))
	{
		echo "<table>";
		echo "<tr><th>Site</th><th>New owners</th></tr>";
		echo "<tr><td>";
		echo urldecode($response->id);
		echo "</td><td>";
		foreach ($response->owners as $responseOwners)
		{
			echo "$responseOwners<br>";
		}
		echo "</td></tr>";
		echo "</table>";
		
	}
	
	/*$newsiteOwners = array("owners"=>$site->owners);
	echo "Newsite Owners";
	print_r($newsiteOwners);
	echo "<br>";
	
	$reponse = $service->webResource->update($site->id, $newsiteOwners);*/
	
	#print_r($reponse);
	
	#echo "End response<br>";
	echo "<br>";
	
	#$site->setId($id);
	#$owners = $site->getOwners();
	
	#print_r($owners);
		
/*	$request = new Google_Service_SiteVerification_SiteVerificationWebResourceResource(array(
  'site' => array(
    'type' => 'INET_DOMAIN',
    'identifier' => 'acme.com'
  )
));
	$service->webResource->insert($verification_method, $request);
 */
	
}
?>

<div class="box">
<?php if (isset($authUrl)): ?>
  <div class="request">
    <a class='login' href='<?= $authUrl ?>'>Connect Me!</a>
  </div>
<?php elseif($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
<?php else: ?>
  <form method="POST">
	Email address: <input type="text" value="" name=email_address><br>
    <input type="submit" value="Click here to assign sites to the new user" />
  </form>
<?php endif ?>
</div>

<?
//pageFooter(__FILE__) 
?>