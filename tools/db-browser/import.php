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

$database = $_GET['db'] ?? "";
$importMethod = $_GET['import-method'] ?? NULL;
$databasesPage = $_GET['databases-page'] ?? 1;

if ($database == "") {
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
    <div class='row'>
      <div class='col'>
        <p class='h2'>
          Database: <i class='text-secondary'>is not selected</i>
          &nbsp;<a href='databases.php' class='btn btn-primary'>Select database</a>
        </p>
      </div>
    </div>
  ";

  exit();
}

$data = NULL;

if (isset($_FILES['data'])) {
  if ($_FILES['data']['error']) {
    echo "
      <div class='text-center'>
        <div class='h3 text-danger'>An error occurred</div>

        <a 
          href='import.php?db={$database}'
          class='btn btn-primary mt-3'
        ><i class='fa-solid fa-arrow-left'></i> Back to import</a>
      </div>
    ";

    exit;
  }
  
  $data = file_get_contents($_FILES['data']['tmp_name']);
}

if ($data == NULL) {
  $data = $_POST['data'] ?? "";
}

if (empty($data)) {
  if ($importMethod == NULL) {
    echo "
      <a 
        href='databases.php?databases-page={$databasesPage}'
        class='btn btn-primary'
      ><i class='fa-solid fa-arrow-left'></i> Back to list of databases</a>
  
      <div class='text-center'>
        <h1 class='mb-3 text-primary'>Import data to <b>{$database}</b></h1>
      </div>
  
      <div class='m-5 p-5'>
        <div class='row'>
          <div class='col-6 text-right'>
            <a 
              href='import.php?db={$database}&import-method=raw' 
              class='btn btn-outline-primary btn-lg export' 
            ><i class='fa-regular fa-file'></i> Import raw JSON</a>
          </div>
          <div class='col-6'>
            <button
              class='btn btn-outline-primary btn-lg export' 
              onclick='document.getElementById(\"json-file-upload\").click();'
            ><i class='fa-solid fa-arrow-up'></i> Import JSON file</button>
          </div>
        </div>
      </div>
  
      <form method='POST' enctype='multipart/form-data'>
        <input 
          name='data'
          type='file' 
          id='json-file-upload' 
          onchange='$(this).closest(\"form\").submit();'
          style='display:none;'
        /> 
      </form>
    ";
  
    exit;
  } else if ($importMethod == 'raw') {
    echo "
      <a 
        href='import.php?db={$database}'
        class='btn btn-primary'
      ><i class='fa-solid fa-arrow-left'></i> Back to import</a>

      <div class='text-center'>
        <h1 class='mb-3 text-primary'>Paste raw JSON data</b></h1>
      </div>

      <form method=POST>
        <div class='row'>
          <div class='col-12'>
            <textarea
              name='data'
              style='width:100%;height:calc(100vh - 300px)'
              placeholder='Put your JSON here...'
            >{$data}</textarea>
          </div>
          <div class='col-12 mt-2'>
            <a
              class='btn btn-primary'
              href='javascript:void(0);'
              onclick='$(this).closest(\"form\").submit();'
            >Start import</a>
          </div>
        </div>
      </form>
    ";
  }
} else {
  $api = new \SondiePhpClient\Client\Client(getApiConfig());
  $api->getAccessToken();
  $api->setDatabase($database);

  $errors = [];
  $log = [];

  $schemas = loadSchemas($api);
  $records = json_decode($data, TRUE);
  $recordIdConversionTable = [];

  // to avoid cross-reference problems, the classes must be imported in a
  // certain order
  $classesImportOrder = [
    // first, classes with no ReferencedClass
    "Actors.Roles",

    // then the other classes
    "Actors.Robots",
    "Actors.Persons",
    "Actors.Teams",
    "Applications",
    "Assets.Tangibles.Tools",
    "Methods",
    "Assets.Intangibles.Documents",
    "Safety.Risks.Categories",
    "Safety.Risks.Register",
    "Tasks",
    "Assets.Intangibles.Calculations.DoseUptake",
    "PlantData.SiteStructure.Rooms",
    "Assets.Intangibles.Measurements.CartesianMeasurementSets",
    "Assets.Intangibles.Measurements.CartesianMeasurements",
    "Assets.Intangibles.Measurements.PartMeasurements",
    "Assets.Intangibles.SpatialTemporals",
    "Assets.Intangibles.VirtualPostIts",
    "Assets.Tangibles.Consumables",
    "Assets.Tangibles.Equipments",
    "Safety.Regulatory.Frameworks",
    "Safety.Regulatory.ClearanceLimits", // tu nieco nesedi
    "Safety.Regulatory.WasteCategories",
    "Safety.Regulatory.WasteAcceptanceCriterias",
    "Wastes.PackageTypes",
    "Assets.Tangibles.Parts",
    "Costs.Estimations",
    "Costs.UnitCostFactorSets",
    "Events",
    "Materials",
    "PlantData.NuclideVectors",
    "PlantData.RadiationSources",
    "PlantData.SiteStructure.Buildings",
    "PlantData.SiteStructure.Floors",
    "PlantData.Space.Cartesian",
    "PlantData",
    "Safety.States",
    "Wastes.ManagementProcesses",
    "Wastes.Sorters",
    "Wastes.WasteAcceptanceCriterias",
    "Workplaces",
    "Scenarios",
    "Scenarios.Runs"
  ];
  
  $importedRecords = [];

  foreach ($classesImportOrder as $classToImport) {
    foreach ($records as $record) {
      try {
        validateRecord($record, $schemas);

        if ($record['class'] != $classToImport) continue;

        // Convert RecordIds in the record's content
        $recordContentStr = json_encode($record['content']);
        foreach ($recordIdConversionTable as $convertFrom => $convertTo) {
          $recordContentStr = str_replace(
            '"'.$convertFrom.'"',
            '"'.$convertTo.'"',
            $recordContentStr
          );
        }
        $record['content'] = json_decode($recordContentStr, TRUE);

        // Fetch original record
        $originalRecord = $api->getRecord($record['_id']);

        $importedRecords[$classToImport][] = [
          '_id' => $record['_id'],
          'type' => 'updated'
        ];

        $importedRecordId = $api->updateRecord(
          $record['_id'], 
          [
            "class" => $record['class'],
            "content" => $record['content'],
          ]
        );
      } catch (\SondiePhpClient\Client\Exception\RequestException $e) {
        $exception = json_decode($e->getMessage(), TRUE);

        // If record not exists create him
        if ($exception["responseBody"]["error"] == "The record does not exist") {
          $importedRecords[$classToImport][] = [
            '_id' => $record['_id'],
            'type' => 'inserted'
          ];
          
          $importedRecordId = $api->createRecord([
            "_id" => $record["_id"],
            "class" => $record['class'],
            "content" => $record['content'],
          ]);
        } else {
          $errors[] = $exception['responseBody'];

          echo "
            <script>
              Swal.fire(
                '{$exception['statusCode']}',
                '{$exception['reason']}',
                'error'
              )
            </script>
          ";
        }
      }
    }
  }

  if (!empty($errors)) {
    echo "
      <div style='color:red'>
        <h2>Errors found!</h2><br/>
        ".join("", $errors)."
      </div>
    ";
  } else {
    echo "
      <a 
        href='databases.php?databases-page={$databasesPage}'
        class='btn btn-primary mb-3'
      ><i class='fa-solid fa-arrow-left'></i> Back to list of databases</a>

      <div style='color:green'>
        <h2>Import to the {$database} was successful!</h2> 
        <a 
          href='browse.php?db={$database}'
          class='btn btn-success'
        >Browse imported records</a>
      <br/>
    ";

    foreach ($importedRecords as $recordClass => $records) {
      echo "<h5 class='mt-4'>Imported records from class {$recordClass}. <b>(" . count($records) . ")</b></h5>";

      foreach ($records as $record) {
        if ($record['type'] == 'updated') echo "Updated _id = {$record['_id']} </br>";
        else if ($record['type'] == 'inserted') echo "<b>Created _id = {$record['_id']} </b></br>";
      }
    }

    echo "</div>";
  }

}

echo "
  </body>
  </html>
";

function validateRecord($record, $schemas) {
  if (empty($record['_id'])) {
    throw new \Exception("Record has no _id.");
  }

  if (empty($record['class'])) {
    throw new \Exception("Record {$record['_id']} has no class.");
  }

  // if (empty($schemas[$record['class']])) {
  //   throw new \Exception("Unknown class for record {$record['_id']}.");
  // }

  if (empty($record['content'])) {
    throw new \Exception("Record {$record['_id']} has no content.");
  }

  if (!is_array($record['content'])) {
    throw new \Exception("Content of the record {$record['_id']} has an invalid format.");
  }
}