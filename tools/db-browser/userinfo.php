<?php

/**
 * SONDIE DB Browser
 * Utility to browse and manage the content of SONDIE database.
 *
 * Author: Dusan Daniska, dusan.daniska@wai.sk
 *
 * License: See LICENSE.md file in the root folder of the software package.
 */

require __DIR__."/init.php";
require __DIR__."/includes/header.php";

checkIfClientIsLogged();

echo "
  <header class='mx-auto' style='position:absolute;top:0;left:0;right:0;width:30%'>
    <nav class='navbar-light bg-light rounded-bottom'>
      <ul class='navbar-nav navbar-expand-lg justify-content-center pt-2'>
        <li class='nav-item p-3'>
          <a class='nav-link' href='index.php'><i class='fa-solid fa-house-chimney'></i> Home</a>
        </li>
        <li class='nav-item p-3'>
          <a class='nav-link' href='databases.php'><i class='fa-solid fa-list'></i> Databases</a>
        </li>
      </ul>
    </nav>
  </header>
";

try {

  $apiConfig = getApiConfig();

  // initiate API client
  $api = new \SondiePhpClient\Client\Client($apiConfig);

  // get access token
  $api->getAccessToken();

  echo "
    <div class='container'>
      <div class='card'>
        <div class='card-header'>
          <p class='h3'>My profile</p>
        </div>
        <div class='card-body'>
          <div class='row p-3'>
            <p class='h4'>Username: <b class='text-primary'>{$apiConfig['userName']}</b></p>
          </div>
          <div class='row p-3'>
            <p class='h4'>Client ID: <b class='text-primary'>{$apiConfig['clientId']}</b></p>
          </div>
          <div class='row p-3'>
          <p class='h4'>Token endpoint: <b class='text-primary'>{$apiConfig['iamTokenEndpoint']}</b></p>
        </div>
        <div class='row p-3'>
        <p class='h4'>API endpoint: <b class='text-primary'>{$apiConfig['apiEndpoint']}</b></p>
      </div>
        </div>
      </div>
    </div>
  ";

} catch (\GuzzleHttp\Exception\ConnectException $e) {
  echo "
    <script>
      Swal.fire(
        'Unable to get ACCESS TOKEN',
        '".$e->getMessage()."',
        'question'
      )
    </script>
  ";
} catch(\GuzzleHttp\Exception\ClientException $e) {
  $responseBody = @json_decode($e->getResponse()->getBody(true), TRUE);
  echo "
    <script>
      Swal.fire({
        title:'{$responseBody['error_description']}',
        icon: 'error'
      })
    </script>
  ";
}

require __DIR__."/includes/footer.php";
