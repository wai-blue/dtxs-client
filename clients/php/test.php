<?php

$configFile = $argv[1] ?? __DIR__ . '/config.json';

if (!is_file($configFile)) exit("Config file does not exist. Copy config.template.json to config.json and configure all parameters.\n");

$config = @json_decode(file_get_contents($configFile), true);

if (!isset($config['dtxsClient']['clientId']) || empty($config['dtxsClient']['clientId'])) exit("Config is missing the value for 'clientId'.\n");
if (!isset($config['dtxsClient']['clientSecret']) || empty($config['dtxsClient']['clientSecret'])) exit("Config is missing the value for 'clientSecret'.\n");
if (!isset($config['dtxsClient']['userName']) || empty($config['dtxsClient']['userName'])) exit("Config is missing the value for 'userName'.\n");
if (!isset($config['dtxsClient']['userPassword']) || empty($config['dtxsClient']['userPassword'])) exit("Config is missing the value for 'userPassword'.\n");
if (!isset($config['dtxsClient']['oauthEndpoint']) || empty($config['dtxsClient']['oauthEndpoint'])) exit("Config is missing the value for 'oauthEndpoint'.\n");
if (!isset($config['dtxsClient']['dtxsEndpoint']) || empty($config['dtxsClient']['dtxsEndpoint'])) exit("Config is missing the value for 'dtxsEndpoint'.\n");

if (!is_dir(__DIR__ . '/../../vendor')) exit("Vendor folder not found. Run 'composer install' in " . realpath(__DIR__ . '/../..'));

require(__DIR__ . '/../../vendor/autoload.php');

function normstrlen(string $str, int $len) {
  if (strlen($str) < $len) { return str_pad($str, $len, ' '); }
  else if (strlen($str) > $len) { return substr($str, 0, $len - 3) . '...'; }
  else { return $str; }
}

function settermcolor(string $colorName) {
  $sequences = [
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'cyan' => "\033[36m",
    'white' => "\033[37m",
    'bg-default' => "\033[49m",
    'bg-cyan' => "\033[46m",
  ];

  if (isset($sequences[$colorName])) {
    echo $sequences[$colorName];
  }
}

function loglastrequest(array $request) {
  settermcolor('cyan');
  echo "-> ";
  settermcolor('bg-cyan');
  echo "Sent {$request['method']} request to {$request['endpoint']}";
  settermcolor('bg-default');
  settermcolor('cyan');
  echo "\n";

  echo "-> ";
  settermcolor('bg-cyan');
  echo "Body: " . json_encode($request['body']);
  settermcolor('bg-default');
  echo "\n";
}

// initiate API client
$api = new \DtxsPhpClient\Loader([
  "clientId" => $config['dtxsClient']['clientId'],
  "clientSecret" => $config['dtxsClient']['clientSecret'],
  "userName" => $config['dtxsClient']['userName'],
  "userPassword" => $config['dtxsClient']['userPassword'],
  "oauthEndpoint" => $config['dtxsClient']['oauthEndpoint'],
  "dtxsEndpoint" => $config['dtxsClient']['dtxsEndpoint'],
  "debugFile" => __DIR__ . "/test.log", // log file
]);

settermcolor('white');
echo "DTXS-client CLI test script.\n";

echo "\n";
settermcolor('yellow');
echo "DTXS endpoint: {$config['dtxsClient']['dtxsEndpoint']}\n";
echo "OAUTH endpoint: {$config['dtxsClient']['oauthEndpoint']}\n";
echo "clientId: {$config['dtxsClient']['clientId']}\n";
echo "clientSecret: " . substr($config['dtxsClient']['clientSecret'], 0, 6) . "...\n";
echo "userName: {$config['dtxsClient']['userName']}\n";
echo "\n";

settermcolor('green');
echo "Authorizing...\n";
$api->getAccessToken();
settermcolor('cyan');
echo "Received access token received for the client '{$config['dtxsClient']['clientId']}'. Length: " . strlen($api->accessToken) . "\n";

$clih = fopen("php://stdin", "r");

$action = '';
$activeDatabase = '';
$exit = false;

while (!$exit) {
  settermcolor('yellow');
  echo "What do you want to do? (Use 'help' or 'h' for help) ".(empty($activeDatabase) ? "(no database is activated)" : "(active database is '{$activeDatabase}')").": ";
  $input = trim(fgets($clih));

  if (strpos($input, ' ') !== false) {
    $action = trim(substr($input, 0, strpos($input, ' ')));
    $argument = trim(substr($input, strlen($action)));
  } else {
    $action = trim($input);
    $argument = '';
  }

  settermcolor('white');

  try {
    switch ($action) {
      case 'help': case 'h':
        echo "  'help' or 'h' = this help\n";
        echo "  'db-list' or 'dbl' = list all databases\n";
        echo "  'db-create' or 'dbc' = create new database\n";
        echo "  'db-delete' or 'dbd' = delete database\n";
        echo "  'db-activate' or 'dba' = activate database\n";
        echo "  'rec-list' or 'rl' = list all records\n";
        echo "  'rec-create-random' or 'rcr' = create random record\n";
        echo "  'rec-update' or 'ru' = update record\n";
        echo "  'doc-create-random' or 'dcr' = create random document\n";
        echo "  'doc-list' or 'dl' = list all documents\n";
        echo "  'doc-get' or 'dg' = get document info\n";
        echo "  'doc-download' or 'dd' = download document\n";
        echo "  'doc-update' or 'du' = update document\n";
        echo "  'exit' or 'x' = exit\n";
      break;

      // db-list
      case 'db-list': case 'dbl':
        settermcolor('green');
        echo "Getting list of databases.\n";
        $databases = $api->getDatabases();
        loglastrequest($api->lastRequest);

        settermcolor('cyan');
        echo "| Database                            |\n";
        foreach ($databases as $key => $value) {
          echo "| " . normstrlen($value['name'], 36);
          echo "|\n";
        }
      break;

      // db-create
      case 'db-create': case 'dbc':
        if (empty($argument)) {
          settermcolor('yellow');
          echo "Enter name of a new database: ";
          $dbName = trim(fgets($clih));
        } else {
          $dbName = $argument;
        }

        settermcolor('green');
        echo "Creating database '{$dbName}'.\n";
        $api->createDatabase($dbName);
        loglastrequest($api->lastRequest);
      break;

      // db-delete
      case 'db-delete': case 'dbd':
        if (empty($argument)) {
          settermcolor('yellow');
          echo "Enter name of database to delete: ";
          $dbName = trim(fgets($clih));
        } else {
          $dbName = $argument;
        }

        settermcolor('green');
        echo "Deleting database '{$dbName}'.\n";
        $api->deleteDatabase($dbName);
        loglastrequest($api->lastRequest);
      break;

      // db-act
      case 'db-activate': case 'dba':
        if (empty($argument)) {
          $databases = $api->getDatabases();
          loglastrequest($api->lastRequest);

          settermcolor('yellow');
          echo "Enter name of the database to activate: ";
          $activeDatabase = trim(fgets($clih));
        } else {
          $activeDatabase = $argument;
        }

        settermcolor('green');
        echo "Activating database '{$activeDatabase}'.\n";
        $api->setDatabase($activeDatabase);
      break;

      // rec-list
      case 'rec-list': case 'rl':
        settermcolor('green');
        echo "Getting list of records.\n";
        $records = $api->getRecords();
        loglastrequest($api->lastRequest);

        settermcolor('cyan');
        echo "| UID                                 ";
        echo " | Version";
        // echo " | Author              ";
        // echo " | Owner               ";
        echo " | Confidentiality";
        echo " | Class           ";
        echo " | IFC model       ";
        echo " | IFC GUID        ";
        echo " | Content                                                                                             ";
        echo " |\n";

        foreach ($records as $record) {
          echo "| " . normstrlen($record['uid'], 36);
          echo " | " . normstrlen($record['version'], 7);
          // echo " | " . normstrlen($record['author'], 20);
          // echo " | " . normstrlen($record['owner'], 20);
          echo " | " . normstrlen($record['confidentiality'], 15);
          echo " | " . normstrlen($record['class'], 16);
          echo " | " . normstrlen($record['ifcModel'], 16);
          echo " | " . normstrlen($record['ifcGuid'], 16);
          echo " | " . normstrlen(json_encode($record['content']), 100);
          echo " |\n";
        }
      break;

      // case rec-create-random
      case 'rec-create-random': case 'rcr':
        $classes = [
          1 => "Actors.Persons",
          2 => "Actors.Teams",
          3 => "Assets.Tangibles.Parts",
          // "Assets.Tangibles.Tools",
          // "Safety.Regulatory.WasteCategories",
          // "Tasks",
        ];

        if (empty($argument)) {
          foreach ($classes as $key => $class) {
            echo "  {$key} = {$class}\n";
          }

          settermcolor('yellow');
          echo "Select a class of the new record: ";
          $classIndex = (int) fgets($clih);
        } else {
          $classIndex = (int) $argument;
        }

        if (isset($classes[$classIndex])) {
          $class = $classes[$classIndex];
          $confidentiality = rand(0, 9);
          
          settermcolor('green');
          echo "Creating random record of class '{$class}' and randomly chosen confidentiality {$confidentiality}.\n";

          $recordsCntrl = new \AquilaTwinlabApp\Controllers\App\Database\Records($app);

          $api->createRecord([
            'class' => $class,
            'confidentiality' => $confidentiality,
            'content' => $recordsCntrl->renderRandomContent($class, rand(3, 5)),
          ]);
          loglastrequest($api->lastRequest);

        } else {
          echo "Unknown class.\n";
        }

      break;

      // doc-list
      case 'doc-list': case 'dl':
        settermcolor('green');
        echo "Getting list of documents.\n";
        $documents = $api->getDocuments();
        loglastrequest($api->lastRequest);

        settermcolor('cyan');
        echo "| UID                                 ";
        echo " | Version";
        echo " | Author              ";
        echo " | Owner               ";
        echo " | Confidentiality";
        echo " | Class                         ";
        echo " | Folder UID                          ";
        echo " | Name                ";
        echo " | Size        ";
        echo " |\n";

        foreach ($documents as $document) {
          echo "| " . normstrlen($document['uid'], 36);
          echo " | " . normstrlen($document['version'], 7);
          echo " | " . normstrlen($document['author'], 20);
          echo " | " . normstrlen($document['owner'], 20);
          echo " | " . normstrlen($document['confidentiality'], 15);
          echo " | " . normstrlen($document['class'], 30);
          echo " | " . normstrlen($document['folderUid'], 36);
          echo " | " . normstrlen($document['name'], 20);
          echo " | " . normstrlen(number_format($document['size'] / 1024, 2, ".", " ") . ' kB', 12);
          echo " |\n";
        }
      break;

      // case doc-create-random
      case 'doc-create-random': case 'dcr':
        $classes = [
          1 => "Actors.Persons",
          2 => "Actors.Teams",
          3 => "Assets.Tangibles.Parts",
        ];

        if (empty($argument)) {
          foreach ($classes as $key => $class) {
            echo "  {$key} = {$class}\n";
          }

          settermcolor('yellow');
          echo "Select a class of the new document: ";
          $classIndex = (int) fgets($clih);


          settermcolor('yellow');
          echo "What should be the size of the document (in bytes)? ";
          $fileSize = (int) fgets($clih);

        } else if (strpos($argument, ' ') !== false) {
          list($classIndex, $fileSize) = explode(' ', $argument);
          $classIndex = (int) $classIndex;
          $fileSize = (int) $fileSize;
        } else {
          $classIndex = (int) $argument;
          $fileSize = 1024;
        }

        if (isset($classes[$classIndex])) {
          $class = $classes[$classIndex];
          $confidentiality = rand(0, 9);

          settermcolor('green');
          echo "Creating random document of class '{$class}', size {$fileSize} B and randomly chosen confidentiality {$confidentiality}.\n";

          $tmpRand = rand(1000, 9999);

          $api->createDocument('root', [
            'class' => $class,
            'confidentiality' => $confidentiality,
            'name' => 'rand-' . $tmpRand . '.txt',
            'content' => 'Hello world, random is ' . str_repeat($tmpRand . '. ', round($fileSize / 6)),
          ]);
          loglastrequest($api->lastRequest);

        } else {
          echo "Unknown class.\n";
        }

      break;

      // rec-update
      case 'rec-download': case 'ru':
        if (empty($argument)) {
          settermcolor('yellow');
          echo "Enter UID of record to update: ";
          $recordUid = trim(fgets($clih));
        } else {
          $recordUid = $argument;
        }

        settermcolor('green');
        echo "Downloading original record content '{$recordUid}'.\n";
        $record = $api->getRecord($recordUid);
        loglastrequest($api->lastRequest);

        echo "Randomly modifying record content.\n";

        $recordsCntrl = new \AquilaTwinlabApp\Controllers\App\Database\Records($app);
        $content = $recordsCntrl->renderRandomContent($record['class']);

        echo "Updating record.\n";
        $updatedRecord = $api->updateRecord($recordUid, $content);
        loglastrequest($api->lastRequest);

        settermcolor('cyan');
        // $tmp = json_decode($updatedDocument, true);
        // echo "New version of the updated record is {$tmp['version']}.\n";
        var_dump($updatedRecord);
      break;

      // doc-get
      case 'doc-get': case 'dg':
        if (empty($argument)) {
          settermcolor('yellow');
          echo "Enter UID of document to download: ";
          $documentUid = trim(fgets($clih));
        } else {
          $documentUid = $argument;
        }

        settermcolor('green');
        echo "Getting document info '{$documentUid}'.\n";
        $document = $api->getDocument('root', $documentUid);
        loglastrequest($api->lastRequest);

        settermcolor('cyan');
        echo "  UID = " . $document['uid'] . "\n";
        echo "  Version = " . $document['version'] . "\n";
        echo "  CreateTime = " . $document['createTime'] . "\n";
        echo "  Author = " . $document['author'] . "\n";
        echo "  Owner = " . $document['owner'] . "\n";
        echo "  Class = " . $document['class'] . "\n";
        echo "  Confidentiality = " . $document['confidentiality'] . "\n";
        echo "  Folder UID = " . $document['folderUid'] . "\n";
        echo "  Name = " . $document['name'] . "\n";
        echo "  Size = " . $document['size'] . "\n";
      break;

      // doc-download
      case 'doc-download': case 'dd':
        if (empty($argument)) {
          settermcolor('yellow');
          echo "Enter UID of document to download: ";
          $documentUid = trim(fgets($clih));
        } else {
          $documentUid = $argument;
        }

        settermcolor('green');
        echo "Downloading document '{$documentUid}'.\n";
        $document = $api->downloadDocument('root', $documentUid);
        loglastrequest($api->lastRequest);

        settermcolor('cyan');
        echo $document . "\n";
      break;

      // doc-update
      case 'doc-download': case 'du':
        if (empty($argument)) {
          settermcolor('yellow');
          echo "Enter UID of document to update: ";
          $documentUid = trim(fgets($clih));
        } else {
          $documentUid = $argument;
        }

        settermcolor('green');
        echo "Downloading original document content '{$documentUid}'.\n";
        $content = $api->downloadDocument('root', $documentUid);
        loglastrequest($api->lastRequest);
        echo "Randomly modifying document content.\n";

        $content = 'Update @ ' . date('Y-m-d H:i:s') . "\n" . $content;

        echo "Updating document.\n";
        $updatedDocument = $api->updateDocument('root', $documentUid, $content);
        loglastrequest($api->lastRequest);

        settermcolor('cyan');
        $tmp = json_decode($updatedDocument, true);
        echo "New version of the updated document is {$tmp['version']}.\n";
      break;

      // case exit
      case 'exit': case 'x':
        $exit = true;
      break;

      // default
      default:
        settermcolor('red');
        echo "Don't know what to do.\n";
      break;
    }
  } catch (\Exception $e) {
    $error = @json_decode($e->getMessage(), true);
    settermcolor('red');
    echo "!!! ERROR. Code: {$error['statusCode']}. Reason: {$error['reason']}\n";
    if (isset($error['responseBody']['error'])) {
      echo "!!! {$error['responseBody']['error']}\n";
    }
  }
}

settermcolor('white');
echo "\n";
echo "Exiting.\n";
