<?php

/**
 * SONDIX DB Browser
 * Utility to browse and manage the content of SONDIX database.
 *
 * Author: Dusan Daniska, dusan.daniska@wai.sk
 *
 * License: See LICENSE.md file in the root folder of the software package.
 */

require __DIR__."/init.php";
require __DIR__."/includes/header.php";

checkIfClientIsLogged();

$f = ($_GET['f'] ?? "");

if (empty($f)) exit("No file to download specified.");

$api = new \DtxsPhpClient\Client\Client(getApiConfig());

$fileContents = $api->downloadFile($f);

echo "<pre>{$fileContents}</pre>";


/*

********************** pokusy s STS

require __DIR__."/init.php";
require __DIR__."/includes/header.php";

checkIfClientIsLogged();

$f = ($_GET['f'] ?? "");

if (empty($f)) exit("No file to download specified.");

$api = new \DtxsPhpClient\Client\Client(getApiConfig());

// $fileContents = $api->downloadFile($f);

// initiate S3 client
// $api->s3Client = new \Aws\S3\S3Client([
//   'version' => 'latest',
//   'region'  => 'us-east-1',
//   'endpoint' => $api->s3Endpoint,
//   'use_path_style_endpoint' => true,
//   'credentials' => [
//     'key'    => $this->userName,
//     'secret' => $this->userPassword,
//   ],
//   'http' => ['verify' => FALSE],
// ]);

    // $response = $api->guzzle->get(
    //   $api->s3Endpoint."?Action=AssumeRoleWithWebIdentity&WebIdentityToken=".$api->getAccessToken()."&Version=2011-06-15"
    // );

// var_dump($response);

// see https://gist.github.com/manics/305f4cc56d0ac6431893cde17b1ba8c4
// see https://docs.min.io/minio/baremetal/security/openid-external-identity-management/configure-openid-external-identity-management.html#minio-external-identity-management-openid-configure

// // https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials_provider.html
// $provider = \Aws\Credentials\CredentialProvider::assumeRoleWithWebIdentityCredentialProvider();
// $provider = \Aws\Credentials\CredentialProvider::memoize($provider);

// $stsClient = new Aws\Sts\StsClient([
//   'endpoint' => $api->s3Endpoint,
//   'region' => 'us-east-2',
//   'version' => '2011-06-15',
//   'credentials' => [
//     'key'    => 'minioadmin',
//     'secret' => 'minioadmin'
//   ],
//   // 'debug' => TRUE,
// ]);

try {
  $accessToken = $api->getAccessToken([
    'scope' => 'openid web-origins offline_access phone roles address email profile microprofile-jwt openid',
  ]);
  // // var_dump($accessToken);
  // $result = $stsClient->AssumeRoleWithWebIdentity([
  //   'WebIdentityToken' => $accessToken,
  //   'RoleArn' => "arn:aws:iam::123456789012:role/xaccounts3access",
  //   'RoleSessionName' => "default",
  // ]);
  // var_dump($result);

  // $url = $api->s3Endpoint."?Action=AssumeRoleWithWebIdentity&WebIdentityToken={$accessToken}&Version=2011-06-15";
  // var_dump(file_get_contents($url));
  $container = [];
  $stack = GuzzleHttp\HandlerStack::create();
  $stack->push(GuzzleHttp\Middleware::history($container));

  $response = $api->guzzle->request(
    "GET",
    $api->s3Endpoint,
    [
      'body' => json_encode([
        "Action" => "AssumeRoleWithWebIdentity",
        "WebIdentityToken" => $accessToken,
        "Version" => "2011-06-15",
        "DurationSeconds" => "86000",
      ]),
      'handler' => $stack,
    ]
  );

  var_dump($response);

} catch(Exception $e) {

  foreach ($container as $transaction) {
    var_dump($transaction['request']);
    echo (string) $transaction['request']->getBody(); // Hello World
  }
  echo "<br/><br/><br/><br/>ERROR!!!";var_dump(get_class($e), $e->getMessage());
  exit();
}

$s3Client = new \Aws\S3\S3Client([
  // 'version'     => '2006-03-01',
  // 'region'      => 'us-west-2',

  'version' => 'latest',
  'region'  => 'us-east-1',
  'endpoint' => $api->s3Endpoint,
  'use_path_style_endpoint' => true,
  // 'credentials' => [
  //   'key'    => $this->userName,
  //   'secret' => $this->userPassword,
  // ],
  'http' => ['verify' => FALSE],

  'credentials' =>  [
    'key'    => $result['Credentials']['AccessKeyId'],
    'secret' => $result['Credentials']['SecretAccessKey'],
    'token'  => $result['Credentials']['SessionToken']
  ]
]);


echo "<pre>{$fileContents}</pre>";

*/