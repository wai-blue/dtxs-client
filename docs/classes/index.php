<?php

ini_set('display_errors', TRUE);

if (php_sapi_name() === 'cli') {
  $arguments = getopt(
    "o:v:",
  );
  $view = $arguments["v"] ?? "browse";
  $outputFile = $arguments["o"] ?? "";
} else {
  $view = $_GET["view"] ?? "browse";
  $outputFile = "";
}

if (
  !empty($view)
  && strpos($view, ".") === FALSE
  && is_file(__DIR__."/views/{$view}.php")
) {
  if (!empty($outputFile)) {
    ob_start();
  }

  require(__DIR__."/views/{$view}.php");

  if (!empty($outputFile)) {
    file_put_contents(__DIR__."/{$outputFile}", ob_get_contents());
    ob_end_clean();
  }
}