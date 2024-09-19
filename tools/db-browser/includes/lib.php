<?php

/**
 * SONDIX DB Browser
 * Utility to browse and manage the content of SONDIX database.
 *
 * Author: Dusan Daniska, dusan.daniska@wai.sk
 *
 * License: See LICENSE.md file in the root folder of the software package.
 */

global $addEnumsToSchema_allRecordsByClass;
$addEnumsToSchema_allRecordsByClass = NULL;

/**
 * Converts SONDIX-specific JSON schema properties to enuv values in
 * the JSON editor.
 *
 * @param  object $api SONDIX API client object
 * @param  array $schema Schema to be extended by enum values
 * @return array Extended schema
 */
function addEnumsToSchema($api, $schema) {
  global $addEnumsToSchema_allRecordsByClass;

  if ($addEnumsToSchema_allRecordsByClass === NULL) {
    $addEnumsToSchema_allRecordsByClass = [];

    $records = $api->getRecords();
    foreach ($records as $record) {
      if (empty($addEnumsToSchema_allRecordsByClass[$record['class']])) {
        $addEnumsToSchema_allRecordsByClass[$record['class']] = [];
      }

      $addEnumsToSchema_allRecordsByClass[$record['class']][] = $record;
    }
  }

  if (is_array($schema)) {
    foreach ($schema as $key => $value) {
      $schema[$key] = addEnumsToSchema($api, $value);
    }
  }

  if (!empty($schema["_SONDIX"]["ReferencedClass"])) {

    $referencedClasses = [];

    if (is_string($schema["_SONDIX"]["ReferencedClass"])) {
      $referencedClasses = [$schema["_SONDIX"]["ReferencedClass"]];
    } else if (is_array($schema["_SONDIX"]["ReferencedClass"])) {
      $referencedClasses = $schema["_SONDIX"]["ReferencedClass"];
    }

    $schema["enum"] = [""];
    $schema["options"]["enum_titles"] = [""];

    foreach ($referencedClasses as $referencedClass) {
      // $records = $api->getRecords(["class" => $referencedClass]);
      $records = $addEnumsToSchema_allRecordsByClass[$referencedClass] ?? [];

      foreach ($records as $record) {
        $tmp = $record['content'];
        unset($tmp['RecordInfo']);

        $schema["enum"][] = $record['_id'];
        $schema["options"]["enum_titles"][] = json_encode($tmp);
      }

    }
  }

  return $schema;
}

function loadSchemas($api) {
  $schemas = [];

  $schemasDir = __DIR__."/../../api-classes/render/schemas";

  foreach (scandir($schemasDir) as $file) {
    if (in_array($file, [".", ".."])) continue;

    $schema = str_replace(".json", "", $file);
    $schemas[$schema] = json_decode(file_get_contents("{$schemasDir}/{$file}"), TRUE);
  }

  $records = $api->getRecords();

  if (count($records) < 2000) { 
    $schemas = addEnumsToSchema($api, $schemas);
  }

  return $schemas;
}

function setClientData(array $clientData = []) : void {
  $_SESSION["clientData"] = $clientData;
}

function getClientData() : array {
  return isset($_SESSION["clientData"]) ? $_SESSION["clientData"] : [];
}

function checkIfClientIsLogged() : void {
  if (empty(getClientData())) {
    header("Location: login.php");
  }
}

function getApiConfig() : array {
  global $apiConfig;
  return array_merge(getClientData(), $apiConfig);
}

function formatRecordContentToHtml($content, $database, $api, $referenceDetailUrl = "edit.php", $expandReferences = FALSE) {

  $contentHtml = json_encode(
    $content,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
  );

  $contentHtml = trim($contentHtml, "{} "); // remove starting and trailing {}
  $contentHtml = preg_replace('/\n    /', "\n", $contentHtml);
  $contentHtml = trim($contentHtml);
  $contentHtml = preg_replace_callback(
    '/"File": "(.+)"/',
    function($matches) {
      return "\"File\": \"<a
        href='download.php?f=".urlencode($matches[1])."'
        target=_blank
        >{$matches[1]}</a>\"
      ";
    },
    $contentHtml
  );

  preg_match_all('/"([a-f\d]{24})"/', $contentHtml, $tmpMatches);
  $tmpReferenceIds = array_unique(array_map(function($m) { return $m; }, $tmpMatches[1]));

  if (count($tmpReferenceIds) > 0) {
    foreach ($tmpReferenceIds as $tmpReferenceId) {
      try {
        $tmpReferenceRecord = $api->getRecord($tmpReferenceId);
        if ($tmpReferenceRecord === NULL) continue;

        if ($expandReferences) {
          $tmpReferenceHtml = json_encode($tmpReferenceRecord);
        } else {
          $tmpTitle = json_encode($tmpReferenceRecord, JSON_PRETTY_PRINT);
          $tmpTitle = str_replace("'", "`", $tmpTitle);
          $tmpTitle = str_replace('"', "`", $tmpTitle);
        }

        $contentHtml = str_replace(
          '"'.$tmpReferenceRecord['_id'].'"',
          "<a
              href='{$referenceDetailUrl}?db={$database}&_id={$tmpReferenceRecord['_id']}'
              style='cursor:help;".(empty($tmpReferenceRecord['_id']) ? "color:red;" : "")."'
              target=_blank
              ".($expandReferences ? "" : "title='{$tmpTitle}'")."
              ".(empty($tmpReferenceRecord['_id']) ? "" : "
                onmouseover='highlightTableRow(\"{$tmpReferenceRecord['_id']}\");'
                onmouseout='$(\"#records tr\").removeClass(\"highlighted\");'
              ")."
            >{$tmpReferenceRecord['_id']}</a>"
          .($expandReferences ? "<div style='margin-left:3em;font-size:0.65em;width:80em;overflow-wrap:anywhere;white-space:normal'>{$tmpReferenceHtml}</div>" : "")
          ,
          $contentHtml
        );
      } catch (\SondixPhpClient\Client\Exception\RequestException $e) {
        //$exception = json_decode($e->getMessage(), TRUE);
      }
    }
  }

  return $contentHtml;
}

function formatContent(string $content) {
  $content = json_decode($content, TRUE);

  foreach ($content as $contentKey => $contentValue) {
    switch ($contentKey) {
      case "UnitCostFactors":
      case "Costs":
      case "ActivityLimit":
      case "ChemicalComposition":
      case "Isotopes":
      case "ChemicalComposition":
      case "WorkDifficultyFactors":
      case "ContaminationLimit":
      case "Requirements":
      case "Parameters":
      case "OuterContaminationLimit":
      case "InnerContaminationLimit":
        $content[$contentKey] = json_decode($contentValue, TRUE) ?? [];
      break;
      case "Value":
        foreach ($contentValue as $contentValueKey => $contentValueValue) {
          if (is_string($contentValueValue)) {
            $content[$contentKey][$contentValueKey] = json_decode($contentValueValue, TRUE) ?? [];
          }
        }
      break;
    }
  }

  return $content;
}

function titleInLookupRecord($tmpLookupRecord) {
  $replaced = preg_replace_callback('/([a-f\d]{24})/', function ($id) { return "{$id[0]} "; }, json_encode($tmpLookupRecord));

  return str_replace("'", "`", json_encode(json_decode($replaced), JSON_PRETTY_PRINT));
}