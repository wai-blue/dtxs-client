<?php

$recordId = $_GET['_id'] ?? "";
$database = $_GET['db'] ?? "";

require __DIR__."/init.php";

checkIfClientIsLogged();

if (!empty($database) && !empty($recordId)) {
  try {
    $api = new \SondiePhpClient\Client\Client(getApiConfig());
    $api->getAccessToken();
    $api->setDatabase($database);

    $allRecords = $api->getRecords();
    $record = $api->getRecord($recordId);

    $tmpContent = $record['content'];
    unset($tmpContent['RecordInfo']);

    echo "
      <h1>Record</h1>
      <div style='font-family:courier new'>
        Database: {$database}<br/>
        Record ID: <b>{$recordId}</b></br>
        Record class: {$record['class']}</br>
        <br/>
        Record content:
      </div>
      <div style='background:#EEEEEE;border:1px solid #E0E0E0;padding:1em;display:inline-block'>
        <pre>".formatRecordContentToHtml($tmpContent, $database, $api, "record.php", TRUE, $allRecords)."</pre>
      </div>
    ";
  } catch (\SondiePhpClient\Client\Exception\RequestException $e) {
    $exception = json_decode($e->getMessage(), TRUE);

    echo "ERROR: ".$exception;
  }
}