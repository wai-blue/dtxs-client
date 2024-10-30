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

$recordId = $_GET['_id'] ?? "";
$save = $_GET['save'] ?? "";
$database = $_GET['db'] ?? "testDatabase";
$recordToDelete = $_GET['delete'] ?? "";

$displayStart = $_GET['display-start'] ?? 0;
$pageLength = $_GET['page-length'] ?? 10;

try {
  // initiate API client
  $api = new \DtxsPhpClient\Client\Client(getApiConfig());

  $api->getAccessToken();

  $api->setDatabase($database);

  if (!empty($recordToDelete)) {
    $api->deleteRecord($recordToDelete);
    header("Location: browse.php?db={$database}");
  }

  if (empty($recordId)) {
    echo "
      <script>
        Swal.fire(
          'No record id provided',
          '',
          'error'
        )
      </script>
    ";
    exit();
  }

  $record = $api->getRecord($recordId);

  if (!empty($save)) {
    $api->updateRecord($recordId, ["content" => formatContent($save)]);
    header("Location: browse.php?db={$database}&display-start={$displayStart}&page-length={$pageLength}&scroll-to-id={$recordId}");
  }

  $schemas = loadSchemas($api);

  echo "
    <br/>
    <div class='wrapper'>
      <a 
        href='browse.php?db={$database}&display-start={$displayStart}&page-length={$pageLength}&scroll-to-id={$recordId}'
        class='btn btn-primary'
      ><i class='fa-solid fa-arrow-left'></i> Back to list of records</a>
      ".
        (
          $record["class"] != "Database.Information" 
          ? "<button onclick='deleteRecord()' class='btn btn-danger float-right'><i class='fa-solid fa-trash'></i> Delete record</button>"
          : ""
        )
      ."
      <div class='text-center mt-5'>
        <p class='h1 mb-3 text-primary'>Edit record <b>{$recordId}</b></p>
      </div>
      <div id='editor-container' style='height:115vh;overflow:auto;'></div>
      <textarea id='input' disabled style='display:none'></textarea>
      <div style='margin-top:1em'>
        <a
          href='javascript:void(0);'
          onclick='
            let url = \"?db={$database}&_id={$recordId}&display-start={$displayStart}&page-length={$pageLength}&scroll-to-id={$recordId}&save=\" + encodeURIComponent($(\"#input\").val());
            location.href = url;
          '
          class='btn btn-success'
        >Save</a>
        <a href='browse.php?db={$database}&display-start={$displayStart}&page-length={$pageLength}&scroll-to-id={$recordId}' class='btn btn-secondary'>Cancel</a>
      </div>
    </div>
  ";
  echo "
    <script>
      let schemas = ".json_encode($schemas).";

      let schema = {
        'title': schemas['{$record['class']}'].definitions.title,
        'description': schemas['{$record['class']}'].definitions.description,
        'type': 'object',
        'properties': schemas['{$record['class']}'].definitions.properties,
      };

      schema.properties.RecordInfo = {
        'type': 'object',
        'properties': {
          'CreatedOn': { type: 'string', readOnly: true },
          'CreatedBy': { type: 'string', readOnly: true },
          'ModifiedOn': { type: 'string', readOnly: true },
          'ModifiedBy': { type: 'string', readOnly: true },
        }
      }

      let config = {
        theme: 'bootstrap4',
        disable_edit_json: true,
        // disable_properties: true,
        disable_collapse: true,
        required_by_default: true,
        startval: ".json_encode($record['content']).",
        schema: schema
      }

      // https://github.com/jdorn/json-editor/
      var editor = new JSONEditor(document.querySelector('#editor-container'), config)

      editor.on('change', function () {
        document.querySelector('#input').value = JSON.stringify(editor.getValue(), (k, value) => value === undefined ? '' : value)
      })

      function deleteRecord() {
        Swal.fire({
          title: 'Are you sure?',
          html: 'Are you sure you want to delete this record?',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, delete record',
          confirmButtonColor: '#dc3545'
        })
        .then((result) => {
          if (result.isConfirmed) {
            location.href = 'edit.php?db={$database}&delete={$recordId}' 
          }
        });
      }
    </script>
  ";
} catch (\DtxsPhpClient\Client\Exception\RequestException $e) {
  $exception = json_decode($e->getMessage(), TRUE);
  $type = "question";

  if (!empty($exception["responseBody"]["error"])) {
    $exception["reason"] = $exception["responseBody"]["error"];
    $type = "error";
  }

  echo "
    <script>
      Swal.fire(
        '{$exception['statusCode']}',
        '{$exception['reason']}',
        '{$type}'
      )
    </script>
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