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
require(__DIR__."/../lib.php");

use \Symfony\Component\Yaml\Yaml;

$classesTree = Yaml::parse(file_get_contents(__DIR__."/../api-classes.yml"));

$flatClassesList = flatizeClassesTree($classesTree);

function convertPropertyType($type) {
  if (in_array($type, [
    "RecordId", 
    "Reference", 
    "FileURI", 
    "IfcGUID", 
    "Date",
    "DateTime"
  ])) {
    $type = "String";
  }

  if ($type == "Decimal") {
    $type = "Number";
  }

  return $type;
}

function getJsonSchemaProperties($className, $classData, $flatClassesList) {
  $properties = [];

  $isSimpleProperty = ($classData['IsSimpleProperty'] ?? FALSE);

  if ($isSimpleProperty) {
    $classData['Properties'] = [$className => $classData];
  }

  if (!empty($classData['Properties']) && is_array($classData['Properties'])) {
    foreach ($classData['Properties'] as $propName => $propData) {
      $type = $propData["Type"] ?? "";
      $typeConverted = convertPropertyType($type);

      $properties[$propName] = [];

      if (!empty($propData['Definition'])) {
        $defProperties = getJsonSchemaProperties(
          $className, 
          $flatClassesList[$propData['Definition']], 
          $flatClassesList
        );
      }

      if (preg_match('/Array\[(.+)\]/', $typeConverted, $m)) {
        $properties[$propName]["type"] = "array";
        
        if ($m[1] == "Def") {
          $properties[$propName]["format"] = "table";

          if ($propData["Definition"] == '$defs.CartesianPoint') {
            $properties[$propName]["items"] = [
              "type" => "number"
            ];
          } else {
            $properties[$propName]["items"] = [
              "type" => "object",
              "properties" => $defProperties,
            ];
          }
        } else {
          if (in_array($propName, ["DataPoints", "Notes"])) {
            $properties[$propName]["items"] = [
              "type" => "string",
              "format" => "textarea"
            ];
          } else {
            $properties[$propName]["items"] = [
              "type" => strtolower(convertPropertyType($m[1])),
            ];
          }

          if (!empty($propData['ReferencedClass'])) {
            $properties[$propName]['items']['_DTXS']['ReferencedClass'] = $propData['ReferencedClass'];
          }
        }
      } else if ($typeConverted == "Def") {
        switch ($propData["Definition"]) {
          case '$defs.ClassName':
            $properties[$propName] = [
              "type" => "string"
            ];
          break;
          case '$defs.UnitCostFactors':
          case '$defs.Costs':
          case '$defs.NuclideVector':
            $properties[$propName] = [
              "type" => "string",
              "format" => "textarea",
              "properties" => $defProperties,
            ];
          break;
          case '$defs.CartesianPoint':
            $properties[$propName] = [
              "type" => "number",
            ];

            $properties[$propName]['_DTXS']['Type'] = "Decimal";
          break;
          default:
            $properties[$propName] = [
              "type" => "object",
              "properties" => $defProperties,
            ];
        }
      } else {
        $properties[$propName]["type"] = strtolower($typeConverted);

        // Add format date if type date
        switch ($type) {
          case "Date":
            $properties[$propName]["format"] = "date";
          break;
          case "DateTime":
            $properties[$propName]["format"] = "datetime-local";
          break;
          case "Object":
            $properties[$propName]["type"] = "string";
            $properties[$propName]["format"] = "textarea";
          break;
        }

        if (!empty($propData['ValidValues'])) {
          $properties[$propName]["enum"] = $propData['ValidValues'];
        }

        $properties[$propName]['_DTXS']['Type'] = $type;

        if (!empty($propData['ReferencedClass'])) {
          $properties[$propName]['_DTXS']['ReferencedClass'] = $propData['ReferencedClass'];
        }
      }
    }
  }

  return $properties;
}


@mkdir(__DIR__."/../render/schemas");

foreach ($flatClassesList as $fullClassName => $classData) {
// if ($fullClassName !== "Actors.Persons") continue;
  if (strpos($fullClassName, '$def') !== FALSE) continue;

  $properties = getJsonSchemaProperties($fullClassName, $classData, $flatClassesList);

  if (empty($properties)) continue;

  $jsonSchema = [
    "definitions" => [
      "title" => $fullClassName,
      "properties" => $properties,
    ],
  ];

  file_put_contents(
    __DIR__."/../render/schemas/{$fullClassName}.json",
    json_encode($jsonSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
  );
}