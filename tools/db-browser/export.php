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

checkIfClientIsLogged();

$databaseToImport = $_GET['db'] ?? "testDatabase";
$databasesPage = $_GET['databases-page'] ?? 1;
$exportMethod = $_GET['import-method'] ?? null;

if ($exportMethod == null) {
  require __DIR__."/includes/header.php";

  echo "
    <a 
      href='databases.php?databases-page={$databasesPage}'
      class='btn btn-primary'
    ><i class='fa-solid fa-arrow-left'></i> Back to list of databases</a>

    <div class='text-center'>
      <h1 class='mb-3 text-primary'>Export data from <b>{$databaseToImport}</b></h1>
    </div>

    <div class='m-5 p-5'>
      <div class='row'>
        <div class='col-6 text-right'>
          <a 
            href='export.php?db={$databaseToImport}&import-method=raw' 
            class='btn btn-outline-primary btn-lg export' 
            target=_blank
          ><i class='fa-regular fa-file'></i> Show raw JSON</a>
        </div>
        <div class='col-6'>
          <a 
            href='export.php?db={$databaseToImport}&import-method=file' 
            class='btn btn-outline-primary btn-lg export' 
            target=_blank
          ><i class='fa-solid fa-arrow-down'></i> Export JSON file</a>
        </div>
      </div>
    </div>
  ";

  exit;
}

try {
  $api = new \DtxsPhpClient\Client\Client(getApiConfig());

  $api->getAccessToken();

  $api->setDatabase($databaseToImport);

  $allRecords = $api->getRecords();

  header('Content-type: application/json');

  $date = date('Y_m_d');

  if ($exportMethod == 'file') header("Content-disposition: attachment; filename={$databaseToImport}_{$date}.json");
  echo json_encode($allRecords);
} catch (\DtxsPhpClient\Client\Exception\RequestException $e) {
  var_dump($e->getMessage());
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
