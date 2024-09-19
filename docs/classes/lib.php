<?php

/**
 * Performs string replacing for custom tags in the classes definition.
 *
 * @param  string $string Original string
 * @return string String with replaced tags.
 */
function applyCustomTags(string $string) : string {
  $string = preg_replace(
    '/{{([a-zA-Z\.:]+)}}/',
    "<a href='javascript:void(0);' onclick='showClassDetails(\"\\1\");'>\\1</a>",
    $string
  );

  return $string;
}

/**
 * urlizeString
 *
 * @param  mixed $text
 * @return string
 */
function urlizeString(string $text) : string {
  $urlPattern = '@((http|https|ftp)\:?//?)[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(:[a-zA-Z0-9]*)?/?([a-zA-Z0-9\:\-\._\?\,\'/\\\+&amp;%\$#\=~])*[^\.\,\)\(\s]@';

  if (preg_match_all($urlPattern, $text, $matches)) { 
    $tmpUrl = $matches[0][0];
    if (strpos($tmpUrl, "://") === FALSE) $tmp_url = "http://{$tmpUrl}";
    $text = str_replace($matches[0][0], "<a href='{$tmpUrl}' target=_blank>{$matches[0][0]}</a>", $text);
  }

  return $text;
}

/**
 * isDef
 *
 * @param  mixed $className
 * @return bool
 */
function isDef(string $className) : bool {
  return strpos($className, '$defs') !== FALSE;
}

/**
 * Generates random GUID.
 *
 * @return string
 */
function randomGUID() : string {
  mt_srand((int) microtime());

  $charid = md5(uniqid(rand(), true));
  $uuid =
    substr($charid, 0, 8)."-"
    .substr($charid, 8, 4)."-"
    .substr($charid,12, 4)."-"
    .substr($charid,16, 4)."-"
    .substr($charid,20,12)
  ;

  return $uuid;
}

/**
 * getClassReferences
 *
 * @param  mixed $referencedClassName
 * @param  mixed $classesTree
 * @param  mixed $classNamePrefix
 * @param  mixed $fullClassesTree
 * @return void
 */
function getClassReferences($referencedClassName, $classesTree, $classNamePrefix = "", $fullClassesTree = NULL) : array {
  $references = [];

  if ($fullClassesTree === NULL) {
    $fullClassesTree = $classesTree;
  }

  foreach ($classesTree as $className => $classData) {
    $fullClassName = trim("{$classNamePrefix}.{$className}", ".");

    if (isset($classData['Properties']) && is_array($classData['Properties'])) {
      foreach ($classData['Properties'] as $propName => $propData) {
      if (!is_array($propData)) var_dump($propData);
        foreach ($propData as $dataName => $dataValue) {
          if (
            ($dataName == "ReferencedClass" || $dataName == "Definition")
            && (
              (is_string($dataValue) && $dataValue == $referencedClassName)
              || (is_array($dataValue) && in_array($referencedClassName, $dataValue))
            )
          ) {
            $references[] = "{$fullClassName}:{$propName}";
          }
        }
      }
    }

    if (isset($classData['_sub']) && is_array($classData['_sub'])) {
      $subReferences = getClassReferences(
        $referencedClassName,
        $classData['_sub'],
        $fullClassName,
        $fullClassesTree
      );

      $references = array_merge($references, $subReferences);
    }
  }

  return $references;
}

/**
 * getDef
 *
 * @param  mixed $defName
 * @param  mixed $classesTree
 * @return array
 */
function getDef(string $defName, array $classesTree) : array {
  $defs = $classesTree['$defs'] ?? [];
  return $defs["_sub"][$defName] ?? [];
}

/**
 * getSingleExampleValue
 *
 * @param  mixed $type
 * @return mixed
 */
function getSingleExampleValue(array $propData, array $fullClassesTree) {
  $exampleValue = NULL;

  if (!empty($propData['ValidValues']) && is_array($propData['ValidValues'])) {
    shuffle($propData['ValidValues']);
    $exampleValue = reset($propData['ValidValues']);
  } else {
    switch ($propData['Type'] ?? "") {
      case "String": $exampleValue = "any-string-value-random-".rand(1000, 9999); break;
      case "RecordId": $exampleValue = randomGUID(); break;
      case "Reference": $exampleValue = randomGUID(); break;
      case "Date": $exampleValue = date("Y-m-d"); break;
      case "DateTime": $exampleValue = date("Y-m-d")."T".date("H:i:s"); break;
      case "Time": $exampleValue = date("H:i:s"); break;
      case "Decimal":
        $minValue = $propData['MinValue'] ?? -1000;
        $maxValue = $propData['MaxValue'] ?? 1000;
        $decimals = $propData['Decimals'] ?? rand(1, 4);
        $pow = pow(10, $decimals);
        $exampleValue = (float) number_format(rand($minValue * $pow, $maxValue * $pow) / $pow, $decimals, ".", "");
      break;
      case "Number":
        $exampleValue = rand($propData['MinValue'] ?? 100, $propData['MaxValue'] ?? 999);
      break;
      case "Base64Binary":
        $exampleValue = base64_encode("Lorem ipsum dolor sit amet, consectetur");
      break;
      case "FileURI":
        $exampleValue = 
          "/minio/path/to/some-file"
          .(empty($propData['Extension']) ? "" : ".{$propData['Extension']}")
        ;
      break;
      case "IfcGUID": $exampleValue = "1W_HslFTT2WwXj91DxSWxH"; break;
      case "Def":
        $def = getDef(str_replace('$defs.', '', $propData['Definition']), $fullClassesTree);

        $defIsSimpleProperty = ($def['IsSimpleProperty'] ?? FALSE);

        $exampleValue = getExampleValue(
          ($defIsSimpleProperty ? ['___TEMP___' => $def] : ($def['Properties'] ?? [])),
          $fullClassesTree
        );

        if ($defIsSimpleProperty) {
          $exampleValue = $exampleValue['___TEMP___'];
        }

        // $exampleValue = getExampleValue($def['Properties'] ?? [], $fullClassesTree);
      break;
    }
  }

  return $exampleValue;
}

/**
 * getExampleValue
 *
 * @param  mixed $properties
 * @param  mixed $fullClassesTree
 * @return array
 */
function getExampleValue(array $properties, array $fullClassesTree) : array {
  $exampleValue = [];
  
  foreach ($properties as $propName => $propData) {
    if (empty($propData['Example']) && (empty($propData['Examples']))) {
      if (preg_match('/Array\[(.+)\](\{\d+\})?/', $propData['Type'] ?? "", $m)) {
        $exampleValue[$propName] = [];
        $tmpPropData = $propData;
        $tmpPropData['Type'] = $m[1];
        $tmpItemCount = empty($m[2]) ? rand(4, 8) : (int) str_replace('{', '', $m[2]);

        for ($i = 0; $i < $tmpItemCount; $i++) {
          $exampleValue[$propName][] = getSingleExampleValue(
            $tmpPropData,
            $fullClassesTree
          );
        }
      } else {
        $exampleValue[$propName] = getSingleExampleValue(
          $propData,
          $fullClassesTree
        );
      }
    } else if (!empty($propData['Example'])) {
      $exampleValue[$propName] = $propData['Example'];
    } else if (!empty($propData['Examples'])) {
      shuffle($propData['Examples']);
      $exampleValue[$propName] = reset($propData['Examples']);
    } else {
      $exampleValue[$propName] = getSingleExampleValue(
        $propData,
        $fullClassesTree
      );
    }
  }

  return $exampleValue;
}

/**
 * Renders the HTML parts based on the definition of classes
 *
 * @param  array $classesTree Structured definition of classes
 * @param  string $classNameToDisplay Class name to display. Used with "c" argument in the URL.
 * @param  mixed $highlightedProperty Property name to highlight. Used with "p" argument in the URL.
 *
 * @return array [$treeHtml, $detailsHtml, $notesHtml]
 */
function renderClassesTree(
  array $classesTree,
  $classNameToDisplay,
  $highlightedProperty,
  string $outputFormat = "html",
  int $level = 0,
  string $classNamePrefix = "",
  $fullClassesTree = NULL
) : array {
  $treeHtml = "";
  $detailsHtml = "";
  $notesHtml = "";

  if ($fullClassesTree === NULL) {
    $fullClassesTree = $classesTree;
  }

  foreach ($classesTree as $className => $classData) {
    $fullClassName = trim("{$classNamePrefix}.{$className}", ".");
    $fullClassNameSanitized = str_replace([".", "$"], ["-", "_"], $fullClassName);

    $isSimpleProperty = ($classData['IsSimpleProperty'] ?? FALSE);
    $hasSubClasses = is_array($classData['_sub'] ?? NULL);

    if ($isSimpleProperty) {
      $classData['Properties'] = [$className => $classData];
    }

    if ($outputFormat == "html" && !empty($classData['Note'])) {
      $notesHtml .= "
        <p class='bg-warning p-2'>
          <a href='javascript:void(0);' onclick='showClassDetails(\"{$fullClassName}\");'><b>{$fullClassName}</b></a>
          ".nl2br(applyCustomTags(urlizeString($classData['Note'])))."
        </p>
      ";
    }

    $hasChanges = !empty($classData['ChangeLog']);
    if (isset($classData['Properties']) && is_array($classData['Properties'])) {
      foreach ($classData['Properties'] as $propName => $propData) {
        if (!empty($propData['ChangeLog'])) {
          $hasChanges = TRUE;
          break;
        }
      }
    }

    $treeHtml .= "
      <div style='margin-left:".($level*2)."em'>
        <a
          id='{$fullClassNameSanitized}_button'
          class='
            api-class-button btn my-1 d-block text-left
            ".($fullClassNameSanitized == $classNameToDisplay ? "btn-primary" : "btn-light")."
            ".($hasChanges ? "changed" : "")."
          '
          onclick='showClassDetails(\"{$fullClassName}\");'
        >
          {$className}
        </a>
      </div>
    ";

    $detailsHtml .= "
      <div
        id='{$fullClassNameSanitized}_detail'
        class='
          api-class-detail
          {$outputFormat}
          card
          
        '
        style='".($fullClassNameSanitized == $classNameToDisplay ? "display:block;" : "")."'
      >
        <div class='card-body'>
          <h4 class='card-title'>{$fullClassName}</h4>
          ".($outputFormat == "pdf" || empty($classData['ChangeLog']) ? "" : "
            <span style='background:purple;color:white'>
              {$classData['ChangeLog']}
            </span>
          ")."
          ".($outputFormat == "pdf" || empty($classData['Note']) ? "" : "
            <h5 class='card-title'>Notes</h5>
            <p class='card-text bg-warning'>".nl2br(urlizeString($classData['Note']))."</p>
          ")."
    ";

    if ($hasSubClasses && empty($classData['Properties'])) {
      if ($outputFormat == "html") {
        $detailsHtml .= "
          <div style='color:#888888'>
            Select a sub-class from the left to show its properties
          </div>
        ";
      } else {
        $detailsHtml .= "
          <div style='color:#888888'>
            This class does not contain structured content.
          </div>
        ";
      }
    } else {
      $detailsHtml .= "
        <p class='card-text'>".(urlizeString($classData['Description'] ?? "<i>No description.</i>"))."</p>
        <h5 class='card-title'>Properties</h5>
      ";

      // default properties _id and RecordInfo
      if (!isDef($fullClassName)) {
        $classData['Properties'] = 
          [
            "_id" => [
              "Default" => TRUE,
              "Type" => "RecordId",
              "Description" => "Record identificator. Automatically generated.",
            ],
            "RecordInfo" => [
              "Default" => TRUE,
              "Type" => "Def",
              "Definition" => '$defs.RecordInfo',
            ],
          ]
          + ($classData['Properties'] ?? [])
        ;
      }

      // $exampleValue
      $exampleValue = getExampleValue(($classData['Properties'] ?? []), $fullClassesTree);

      // Properties
      if (isset($classData['Properties']) && is_array($classData['Properties'])) {
        $detailsHtml .= "
          <table class='table table-borderless mt-4'>
            <thead class='thead-dark'>
              <tr>
                <th>Property</th>
                <th colspan=2>Definition</th>
              </tr>
            </thead>
            <tbody>
        ";

        foreach ($classData['Properties'] as $propName => $propData) {
          if ($outputFormat == "html" && !empty($propData['Note'])) {
            $notesHtml .= "
              <p class='bg-warning p-2'>
                <a href='javascript:void(0);' onclick='showClassDetails(\"{$fullClassName}:{$propName}\");'><b>{$fullClassName}:{$propName}</b></a>
                ".nl2br(applyCustomTags(urlizeString($propData['Note'])))."
              </p>
            ";
          }

          $propData['Example'] = $exampleValue[$propName] ?? "";

          $rowspan = count($propData) + 1;
          if (!empty($propData['Type'])) $rowspan--;
          if (!empty($propData['Optional'])) $rowspan--;

          $detailsHtml .= "
            <tr
              id='property-{$fullClassNameSanitized}-".str_replace(".", "-", $propName)."'
              style='border-bottom:1px solid #EEEEEE'
              class='
                ".($propData['Default'] ?? FALSE ? "default" : "")."
                ".($propData['Deprecated'] ?? FALSE ? "deprecated" : "")."
                ".(empty($propData['ChangeLog']) ? "" : "changelog")."
                ".($highlightedProperty == $propName ? "highlighted" : "")."
              '
            >
              <td rowspan='{$rowspan}'>
                <b>".htmlspecialchars($propName, ENT_QUOTES)."</b>
                <a
                  href='?c={$fullClassNameSanitized}:{$propName}'
                  style='font-size:0.75em;opacity:0.25;float:right'
                  target=_blank
                >🔗</a>
                <div class='small mt-2'>".($propData['Type'] ?? "<i class='text-danger'>No type defined</i>")."</div>
                ".($propData['Optional'] ?? FALSE ? "<div class='small mt-2 text-primary'>Optional</div>" : "")."
              </td>
            </tr>
          ";
          foreach ($propData as $dataName => $dataValue) {
            if (in_array($dataName, ["Type", "Optional"])) continue;

            $dataValueAsString = "";
            if (is_string($dataValue) || is_numeric($dataValue)) {
              $dataValueAsString = (string) $dataValue;
            } else if (is_array($dataValue)) {
              $dataValueAsString = json_encode($dataValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else if (is_bool($dataValue)) {
              $dataValueAsString = $dataValue ? "TRUE" : "FALSE";
            } else {
              $dataValueAsString = "[".gettype($dataValue)."] ".(string) $dataValue;
            }

            if ($dataName == "ReferencedClass") {
              if (is_string($dataValue)) {
                $referencedClasses = [$dataValue];
              } else {
                $referencedClasses = $dataValue;
              }

              $dataValueAsString = "";
              foreach ($referencedClasses as $tmpClass) {
                $dataValueAsString .= "
                  <a href='javascript:void(0);' onclick='showClassDetails(\"{$tmpClass}\");'>{$tmpClass}</a><br/>
                ";
              }
            } else if ($dataName == "Definition") {
              $dataValueAsString = "
                <a href='javascript:void(0);' onclick='showClassDetails(\"{$dataValueAsString}\");'>{$dataValueAsString}</a>
              ";
            } else {
              $dataValueAsString = applyCustomTags(urlizeString($dataValueAsString));
            }

            if ($dataName == "Note" || $dataName == "Description") {
              $dataValueAsString = nl2br($dataValueAsString);
            }
            if ($dataName == "Example" || $dataName == "Examples" || $dataName == "ValidValues") {
              $dataValueAsString = "<xmp>{$dataValueAsString}</xmp>";
            }

            $detailsHtml .= "
              <tr
                class='".($propData['Default'] ?? FALSE ? "default" : "")."'
              >
                <td>{$dataName}</td>
                <td class='property-data-{$dataName}'>{$dataValueAsString}</td>
              </tr>
            ";
          }
        }
        $detailsHtml .= "
            </tbody>
          </table>
        ";
      } else {
        $detailsHtml .= "<p class='card-text'><i>No properties for this class.</i></p>";
      }

      // Referenced By
      $detailsHtml .= "
        <h5 class='card-title'>Referenced by</h5>
      ";

      $references = (array) getClassReferences($fullClassName, $fullClassesTree);

      if (count($references) === 0) {
        $detailsHtml .= "<p class='card-text'><i>This class is not referenced by another class.</i></p>";
      } else {
        $detailsHtml .= "<p class='card-text'>";
        foreach ($references as $referencingClassName) {
          $detailsHtml .= "
            <a href='javascript:void(0);' onclick='showClassDetails(\"{$referencingClassName}\");'>{$referencingClassName}</a><br/>
          ";
        }
        $detailsHtml .= "</p>";
      }

      if (isDef($fullClassName)) {
        // Example $def value

        $detailsHtml .= "
          <h5 class='card-title'>Example value</h5>
          <xmp class='example-value'>".json_encode($exampleValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS)."</xmp>
        ";

      } else {

        // Example body content
        $bodyContentExample = [
          "class" => $fullClassName,
          "content" => $exampleValue,
        ];

        $detailsHtml .= "
          <h5 class='card-title'>Example value of <i>body</i> in POST /record</h5>
          <xmp class='example-value'>".json_encode($bodyContentExample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS)."</xmp>
        ";

        // Input To
        // $detailsHtml .= "
        //   <h5 class='card-title'>Input to (Used by)</h5>
        // ";

        // if (isset($classData['InputTo']) && is_array($classData['InputTo'])) {
        //   $inputToHtml = "";
        //   foreach ($classData['InputTo'] as $software) {
        //     $inputToHtml .= "
        //       <a
        //         href='?view=software-usage&s={$software}'
        //         class='btn'
        //       >{$software}</a>
        //     ";
        //   }
        //   $detailsHtml .= "<p class='card-text'>{$inputToHtml}</p>";
        // } else {
        //   $detailsHtml .= "<p class='card-text'><i>No software uses this class as an input.</i></p>";
        // }

        // Output From
        // $detailsHtml .= "
        //   <h5 class='card-title'>Output from (Generated by)</h5>
        // ";

        // if (isset($classData['OutputFrom']) && is_array($classData['OutputFrom'])) {
        //   $outputFromHtml = "";
        //   foreach ($classData['OutputFrom'] as $software) {
        //     $outputFromHtml .= "
        //       <a
        //         href='?view=software-usage&s={$software}'
        //         class='btn'
        //       >{$software}</a>
        //     ";
        //   }
        //   $detailsHtml .= "<p class='card-text'>{$outputFromHtml}</p>";
        // } else {
        //   $detailsHtml .= "<p class='card-text'><i>No software generates this class as an output.</i></p>";
        // }

        // Exports
        // if ($outputFormat == "html") {
        //   $detailsHtml .= "
        //     <h5 class='card-title'>Exports</h5>
        //     <p class='card-text'>
        //       <a href='#' class='btn btn-light my-2'>Export to JSON schema</a><br/>
        //       <a href='#' class='btn btn-light my-2'>Export to OWL</a><br/>
        //     </p>
        //   ";
        // }
      }

    }

    //
    $detailsHtml .= "
        </div>
      </div>
    ";

    if (isset($classData['_sub']) && is_array($classData['_sub'])) {
      [$subTreeHtml, $subDetailsHtml, $subNotesHtml] = renderClassesTree(
        $classData['_sub'],
        $classNameToDisplay,
        $highlightedProperty,
        $outputFormat,
        $level + 1,
        $fullClassName,
        $fullClassesTree
      );

      $treeHtml .= $subTreeHtml;
      $detailsHtml .= $subDetailsHtml;
      $notesHtml .= $subNotesHtml;
    }
  }

  return [$treeHtml, $detailsHtml, $notesHtml];
}


function flatizeClassesTree($classesTree, $classNamePrefix = "") {
  $flatClassesList = [];

  foreach ($classesTree as $className => $classData) {
    $fullClassName = trim("{$classNamePrefix}.{$className}", ".");

    $flatClassesList[$fullClassName] = $classData;

    if (isset($classData['_sub']) && is_array($classData['_sub'])) {
      unset($flatClassesList[$fullClassName]['_sub']);

      $flatClassesList = array_merge(
        $flatClassesList,
        flatizeClassesTree($classData['_sub'], $fullClassName)
      );
    }
  }

  return $flatClassesList;
}
