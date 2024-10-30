<?php

/* 
  * DTXS API Classes Visualiser
  *
  * Tool for streamlining the collaboration in developing the DTXS
  * API Classes structure and properties.
  *
  * Author: Dusan Daniska, dusan.daniska@wai.sk 
  *
  */

require(__DIR__."/../vendor/autoload.php");
require(__DIR__."/../common.php");

use \Symfony\Component\Yaml\Yaml;

$classesTree = Yaml::parse(file_get_contents(__DIR__."/../api-classes.yml"));
$softwareToDisplay = $_GET['s'] ?? "";

function getSoftwareUsageInfo(array $classesTree, string $classNamePrefix = "") : array {
  $softwareUsage = [];

  foreach ($classesTree as $className => $classData) {
    $fullClassName = trim("{$classNamePrefix}.{$className}", ".");

    // Input To
    if (isset($classData['InputTo']) && is_array($classData['InputTo'])) {
      foreach ($classData['InputTo'] as $software) {
        if (!isset($softwareUsage[$software])) {
          $softwareUsage[$software] = ["input" => [], "output" => []];
        }

        if (!in_array($fullClassName, $softwareUsage[$software]["input"])) {
          $softwareUsage[$software]["input"][] = $fullClassName;
        }
      }
    }

    // Output From
    if (isset($classData['OutputFrom']) && is_array($classData['OutputFrom'])) {
      foreach ($classData['OutputFrom'] as $software) {
        if (!isset($softwareUsage[$software])) {
          $softwareUsage[$software] = ["input" => [], "output" => []];
        }

        if (!in_array($fullClassName, $softwareUsage[$software]["output"])) {
          $softwareUsage[$software]["output"][] = $fullClassName;
        }
      }
    }

    //
    if (isset($classData['_sub']) && is_array($classData['_sub'])) {
      $subSoftwareUsage = getSoftwareUsageInfo(
        $classData['_sub'],
        $fullClassName
      );

      foreach ($subSoftwareUsage as $software => $usage) {
        if (!isset($softwareUsage[$software])) {
          $softwareUsage[$software] = ["input" => [], "output" => []];
        }

        $softwareUsage[$software]["input"] = array_merge(
          $softwareUsage[$software]["input"],
          $subSoftwareUsage[$software]["input"]
        );

        $softwareUsage[$software]["output"] = array_merge(
          $softwareUsage[$software]["output"],
          $subSoftwareUsage[$software]["output"]
        );
      }
    }
  }

  return $softwareUsage;
}


////////////////////////////////////////////////////////////////////////////////////////////////


$softwareUsage = getSoftwareUsageInfo($classesTree);

$softwaresHtml = "";
$inputsOutputsHtml = "";

foreach ($softwareUsage as $software => $usage) {
  $softwaresHtml .= "
    <div>
      <a
        data-software-hash='".md5($software)."'
        class='software-usage-button button btn my-1 d-block text-left btn-light'
        onclick='showSoftwareUsage(this);'
      >{$software}</a>
    </div>
  ";

  $inputsHtml = "";
  foreach ($usage["input"] as $className) {
    $inputsHtml .= "
      <div>
        <a
          href='?view=browse&c={$className}'
          class='btn'
        >{$className}</a>
      </div>
    ";
  }

  $outputsHtml = "";
  foreach ($usage["output"] as $className) {
    $outputsHtml .= "
      <div>
        <a
          href='?view=browse&c={$className}'
          class='btn'
        >{$className}</a>
      </div>
    ";
  }

  $inputsOutputsHtml .= "
    <div
      data-software-hash='".md5($software)."'
      class='inputs-outputs-details card'
      ".($software == $softwareToDisplay ? "style='display:block'" : "")."
    >
      <div class='card-body'>
        <h4 class='card-title'>{$software}</h4>
        <h5 class='card-title'>Inputs</h5>
        <p class='card-text'>
          ".(empty($inputsHtml) ? "<i>No input to this software.</i>" : $inputsHtml)."
        </p>
        <h5 class='card-title'>Outputs</h5>
        <p class='card-text'>
          ".(empty($outputsHtml) ? "<i>No output by this software.</i>" : $outputsHtml)."
        </p>
      </div>
    </div>
  ";
}

echo \Common::HtmlHeader("Software usage");

echo "
  <style>
    body { padding: 1em; }

    .sofware-list {
      height: calc(100vh - 170px);
      overflow: auto;
    }

    .inputs-outputs-details {
      display: none;
      height: calc(100vh - 170px);
      overflow: auto;
    }
  </style>

  <script>
    function showSoftwareUsage(btn) {

      $('.inputs-outputs-details').hide();
      $('.inputs-outputs-details[data-software-hash=\"' + $(btn).data('software-hash') + '\"]').show();

      $('.software-usage-button').removeClass('btn-primary').addClass('btn-light');
      $(btn).addClass('btn-primary').removeClass('btn-light');
    }
  </script>

  <div style='display:flex;padding:1em;'>
    <div style='flex:1;margin-right:1em;'>
      <div>
        <div class='card-body sofware-list'>
          {$softwaresHtml}
        </div>
      </div>
    </div>
    <div style='flex:4'>
      <div class='inputs-outputs-details card' ".(empty($softwareToDisplay) ? "style='display:block'" : "").">
        <div class='card-body'>
          <p class='card-text'>Select a software on the left.</p>
        </div>
      </div>
      {$inputsOutputsHtml}
    </div>
  </div>
";

echo \Common::HtmlFooter();

