<?php

/**
 * DTXS DB Browser
 * Utility to browse and manage the content of DTXS database.
 *
 * Author: Dusan Daniska, dusan.daniska@wai.sk
 *
 * License: See LICENSE.md file in the root folder of the software package.
 */

require __DIR__."/init.php";
require __DIR__."/includes/header.php";

checkIfClientIsLogged();

$create = ($_GET['create'] ?? "") == "1";
$saveClass = $_GET['class'] ?? "";
$saveContent = $_GET['content'] ?? "";
$databaseToCreateRecord = $_GET['db'] ?? "testDatabase";

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

echo "
  <h2>Create record</h2>
";

try {
  $api = new \DtxsDtxsPhpClient\Client\Client(getApiConfig());

  // get access token
  $api->getAccessToken();

  $api->setDatabase($databaseToCreateRecord);

  if ($create) {
    try {
      $createdRecordId = $api->createRecord([
        "class" => $saveClass,
        "content" => formatContent($saveContent)
      ]);

      header("Location: browse.php?db={$databaseToCreateRecord}");
    } catch (\DtxsPhpClient\Client\Exception\RequestException $e) {
      throw new \DtxsPhpClient\Client\Exception\RequestException($e->getMessage());
    }
  } else {

    $schemas = loadSchemas($api);

    // $schemasOptions = "";
    $schemasButtons = "";
    foreach (array_keys($schemas) as $schema) {
      // $schemasOptions .= "<option value='{$schema}'>{$schema}</option>";
      $schemasButtons .= "
        <a
          href='javascript:void(0)'
          class='btn btn-light d-block text-left mb-1'
          data-schema='{$schema}'
          onclick='init_json_editor(\"{$schema}\");'
        >{$schema}</a>
      ";
    }

    echo "
      <div style='display:flex'>
        <input id='class' type='hidden'>
        <div id='buttons' style='flex:1;margin-right:1em;height:calc(100vh - 13em);overflow:auto'>
          {$schemasButtons}
        </div>
        <div style='flex:3'>
          <div id='editor-container' style='height:calc(100vh - 16em);overflow:auto'></div>
          <br/>
          <textarea id='content' disabled style='display:none'></textarea>
          <a
            id='save_button'
            href='javascript:void(0);'
            style='display:none'
            onclick='
              let url = \"?create=1&db={$databaseToCreateRecord}\"
              url += \"&class=\" + encodeURIComponent($(\"#class\").val());
              url += \"&content=\" + encodeURIComponent($(\"#content\").val());
              location.href = url;
            '
            class='btn btn-primary'
          >Create record</a>
          <a href='browse.php?db={$databaseToCreateRecord}' class='btn btn-secondary'>Cancel</a>
        </div>
      </div>
      <script>
        let schemas = ".json_encode($schemas).";

        function init_json_editor(selectedClass) {
          let editorContainer = $('#editor-container');
          // let selectedClass = $('#class').val();

          editorContainer.empty();
          $('#save_button').hide();

          if (!schemas[selectedClass]) return;

          let schema = {
            'title': schemas[selectedClass].definitions.title,
            'description': schemas[selectedClass].definitions.description,
            'type': 'object',
            'properties': schemas[selectedClass].definitions.properties,
          };

          console.log(schema);

          let config = {
            theme: 'bootstrap4',
            disable_edit_json: true,
            disable_properties: true,
            disable_collapse: true,
            schema: schema
          }

          let editor = new JSONEditor(editorContainer.get(0), config)

          editor.on('change', function () {
            document.querySelector('#content').value = JSON.stringify(editor.getValue())
          });

          $('#class').val(selectedClass);

          $('#save_button').show();
          $('#buttons .btn').removeClass('btn-primary').addClass('btn-light');
          $('#buttons .btn[data-schema=\"' + selectedClass + '\"]').removeClass('btn-light').addClass('btn-primary');
        }

        // init_json_editor();
      </script>
    ";
  }
} catch (\DtxsPhpClient\Client\Exception\RequestException $e) {
  $exception = json_decode($e->getMessage(), TRUE);

  echo "
    <script>
      Swal.fire(
        '{$exception['statusCode']}',
        '{$exception['reason']}',
        'question'
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

require __DIR__."/includes/footer.php";
