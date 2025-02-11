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

function normstrlen(string $str, int $len): string
{
  if (strlen($str) < $len) { return str_pad($str, $len, ' '); }
  else if (strlen($str) > $len) { return substr($str, 0, $len - 3) . '...'; }
  else { return $str; }
}

function settermcolor(string $colorName): void
{
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

function yellow(string $message) { settermcolor('yellow'); echo $message; }
function green(string $message) { settermcolor('green'); echo $message; }
function red(string $message) { settermcolor('red'); echo $message; }
function blue(string $message) { settermcolor('blue'); echo $message; }
function cyan(string $message) { settermcolor('cyan'); echo $message; }
function white(string $message) { settermcolor('white'); echo $message; }

function loglastrequest(array $request): void
{
  cyan("-> ");
  cyan("Sent {$request['method']} request to {$request['endpoint']}");
  cyan("\n");

  $bodyStr = json_encode($request['body']);
  if (strlen($bodyStr) > 300) $bodyStr = substr($bodyStr, 0, 300) . '... (' . strlen($bodyStr) . ' bytes)';
  cyan("-> ");
  cyan("Body: " . $bodyStr);
  cyan("\n");
}

function parsearg(string $argument): array
{
  if (strpos($argument, "'") === false) {
    return explode(' ', $argument);
  } else {
    preg_match_all("/'([^']+)'/", $argument, $m);
    return $m[1];
  }
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

white("DTXS-client CLI test script.\n");
white("\n");
yellow("DTXS endpoint: {$config['dtxsClient']['dtxsEndpoint']}\n");
yellow("OAUTH endpoint: {$config['dtxsClient']['oauthEndpoint']}\n");
yellow("clientId: {$config['dtxsClient']['clientId']}\n");
yellow("clientSecret: " . substr($config['dtxsClient']['clientSecret'], 0, 6) . "...\n");
yellow("userName: {$config['dtxsClient']['userName']}\n");
yellow("\n");

green("Authorizing...\n");

try {
  $api->getAccessToken();
  cyan("Received access token received for the client '{$config['dtxsClient']['clientId']}'. Length: " . strlen($api->accessToken) . "\n");

  $clih = fopen("php://stdin", "r");

  $action = '';
  $activeDatabase = '';
  $exit = false;

  while (!$exit) {
    yellow("What do you want to do? (Use 'help' or 'h' for help) ".(empty($activeDatabase) ? "(no database is activated)" : "(active database is '{$activeDatabase}')").": ");
    $input = trim(fgets($clih));

    if (strpos($input, ' ') !== false) {
      $action = trim(substr($input, 0, strpos($input, ' ')));
      $arguments = parsearg(trim(substr($input, strlen($action))));
    } else {
      $action = trim($input);
      $arguments = [];
    }

    try {
      switch ($action) {
        case 'help': case 'h':
          white("  'help' or 'h' = this help\n");
          white("  'server-info' or 'i' = about the server\n");
          white("  'classes' = list available classes\n");
          white("  'db-list' or 'dbl' = list all databases\n");
          white("  'db-create' or 'dbc' [db-name] = create new database\n");
          white("  'db-delete' or 'dbd' = delete database\n");
          white("  'db-activate' or 'dba' = activate database\n");
          white("  'import-pleiades' or 'ip' = import from PLEIADES JSON file\n");
          white("  'rec-list' or 'rl' = list all records\n");
          white("  'rec-get' or 'rg' = get a record\n");
          white("  'rec-get-history' or 'rgh' = get record history\n");
          white("  'rec-create' or 'rc' = create record\n");
          white("  'rec-update' or 'ru' = update record\n");
          white("  'doc-create-random' or 'dcr' = create random document\n");
          white("  'doc-list' or 'dl' = list all documents\n");
          white("  'doc-get' or 'dg' = get document info\n");
          white("  'doc-download' or 'dd' = download document\n");
          white("  'doc-update' or 'du' = update document\n");
          white("  'exit' or 'x' = exit\n");
        break;

        // about-server
        case 'server-info': case 'i':
          green("Getting server info.\n");
          $serverInfo = $api->getServerInfo();
          loglastrequest($api->lastRequest);

          cyan('Server info: ' . json_encode($serverInfo) . "\n");
        break;

        // classes
        case 'classes':
          green("Getting list of available classes.\n");
          $classes = $api->getClasses();
          loglastrequest($api->lastRequest);

          if (is_array($classes['classes'])) {
            foreach ($classes['classes'] as $class) {
              cyan("  " . $class. "\n");
            }
          } else {
            cyan('No available classes found.');
          }
        break;

        // db-list
        case 'db-list': case 'dbl':
          green("Getting list of databases.\n");
          $databases = $api->getDatabases();
          loglastrequest($api->lastRequest);

          cyan("| Database                            |\n");
          foreach ($databases as $key => $value) {
            cyan("| " . normstrlen($value['name'], 36));
            cyan("|\n");
          }
        break;

        // db-create
        case 'db-create': case 'dbc':
          if (count($arguments) == 0) {
            yellow("Enter name of a new database: ");
            $dbName = trim(fgets($clih));
          } else {
            $dbName = $arguments[0];
          }

          green("Creating database '{$dbName}'.\n");
          $api->createDatabase($dbName);
          loglastrequest($api->lastRequest);
        break;

        // db-delete
        case 'db-delete': case 'dbd':
          if (count($arguments) == 0) {
            yellow("Enter name of database to delete: ");
            $dbName = trim(fgets($clih));
          } else {
            $dbName = $arguments[0];
          }

          green("Deleting database '{$dbName}'.\n");
          $api->deleteDatabase($dbName);
          loglastrequest($api->lastRequest);
        break;

        // db-act
        case 'db-activate': case 'dba':
          if (count($arguments) == 0) {
            $databases = $api->getDatabases();
            loglastrequest($api->lastRequest);

            yellow("Enter name of the database to activate: ");
            $activeDatabase = trim(fgets($clih));
          } else {
            $activeDatabase = $arguments[0];
          }

          green("Activating database '{$activeDatabase}'.\n");
          $api->setDatabase($activeDatabase);
        break;

        // db-act
        case 'import-pleiades': case 'ip':
          $database = $arguments[0] ?? '';
          $file = $arguments[1] ?? '';

          if (empty($database) || empty($file)) {
            red("Usage: import-pleiades '<input-json-file>' '<database-where-to-import>'.\n");
          } else if (!is_file($file)) {
            red("'{$file}' not found.\n");
          } else {
            green("Importing '{$file}' to database '{$database}'.\n");
            $importer = new \DtxsPhpClient\ImportPleiades($api);
            $importer->saveRecords($database, $importer->loadRecordsFromJson($file));
          }
        break;

        // rec-list
        case 'rec-list': case 'rl':
          green("Getting list of records.\n");
          $records = $api->getRecords();
          loglastrequest($api->lastRequest);

          cyan("| UID                                 ");
          cyan(" | Version");
          cyan(" | Confidentiality");
          cyan(" | Class           ");
          cyan(" | IFC model       ");
          cyan(" | IFC GUID        ");
          cyan(" | Content                                                                                             ");
          cyan(" |\n");

          foreach ($records as $record) {
            cyan("| " . normstrlen($record['uid'], 36));
            cyan(" | " . normstrlen($record['version'], 7));
            cyan(" | " . normstrlen($record['confidentiality'], 15));
            cyan(" | " . normstrlen($record['class'], 16));
            cyan(" | " . normstrlen($record['ifcModel'], 16));
            cyan(" | " . normstrlen($record['ifcGuid'], 16));
            cyan(" | " . normstrlen(json_encode($record['content']), 100));
            cyan(" |\n");
          }
        break;

        // rec-get
        case 'rec-get': case 'rg':
          if (count($arguments) == 0) {
            yellow("Enter UID of record to get: ");
            $recordUid = trim(fgets($clih));
            yellow("Enter version of record to get (empty or 0 for the latest version): ");
            $version = (int) trim(fgets($clih));
          } else {
            $recordUid = $arguments[0];
            $version = (int) $arguments[1];
          }

          green("Getting record info '{$recordUid}' ver. {$version}.\n");
          $record = $api->getRecord($recordUid, $version);
          loglastrequest($api->lastRequest);

          cyan("  UID = " . $record['uid'] . "\n");
          cyan("  Version = " . $record['version'] . "\n");
          cyan("  CreateTime = " . $record['createTime'] . "\n");
          cyan("  Author = " . $record['author'] . "\n");
          cyan("  Owner = " . $record['owner'] . "\n");
          cyan("  Class = " . $record['class'] . "\n");
          cyan("  Confidentiality = " . $record['confidentiality'] . "\n");
          cyan("  IfcModel = " . $record['ifcModel'] . "\n");
          cyan("  IfcGuid = " . $record['ifcGuid'] . "\n");
          cyan("  Content = " . json_encode($record['content']) . "\n");
        break;

        // rec-get
        case 'rec-get-history': case 'rgh':
          if (count($arguments) == 0) {
            yellow("Enter UID of record to get: ");
            $recordUid = trim(fgets($clih));
          } else {
            $recordUid = $arguments[0];
          }

          green("Getting record history for '{$recordUid}'.\n");
          $recordHistory = $api->getRecordHistory($recordUid);
          loglastrequest($api->lastRequest);

          cyan(json_encode($recordHistory) . "\n");
        break;

        // case rec-create-random
        case 'rec-create': case 'rc':
          $classes = [
            1 => "Actors.Persons",
            2 => "Actors.Teams",
            3 => "Assets.Tangibles.Parts",
            // "Assets.Tangibles.Tools",
            // "Safety.Regulatory.WasteCategories",
            // "Tasks",
          ];

          if (count($arguments) == 0) {
            foreach ($classes as $key => $class) {
              white("  {$key} = {$class}\n");
            }

            yellow("Select a class of the new record: ");
            $classIndex = (int) fgets($clih);
          } else {
            $classIndex = (int) $arguments[0];
          }

          if (isset($classes[$classIndex])) {
            $class = $classes[$classIndex];
            $confidentiality = rand(0, 9);

            green("Creating class '{$class}' and randomly chosen confidentiality {$confidentiality}.\n");

            yellow("Write a JSON string for the content for the new record: ");
            $jsonString = (string) fgets($clih);
            $content = json_decode(trim($jsonString), true);

            if (isset($content)) {
              $api->createRecord([
                'class' => $class,
                'confidentiality' => $confidentiality,
                'content' => $content
              ]);
              loglastrequest($api->lastRequest);
            } else {
              red("The content was empty or was not a valid JSON string.\n");
            }
          } else {
            red("Unknown class.\n");
          }

        break;

        // doc-list
        case 'doc-list': case 'dl':
          green("Getting list of documents.\n");
          $documents = $api->getDocuments();
          loglastrequest($api->lastRequest);

          cyan("| UID                                 ");
          cyan(" | Version");
          cyan(" | Author              ");
          cyan(" | Owner               ");
          cyan(" | Confidentiality");
          cyan(" | Class                         ");
          cyan(" | Folder UID                          ");
          cyan(" | Name                ");
          cyan(" | Size        ");
          cyan(" |\n");

          foreach ($documents as $document) {
            cyan("| " . normstrlen($document['uid'], 36));
            cyan(" | " . normstrlen($document['version'], 7));
            cyan(" | " . normstrlen($document['author'], 20));
            cyan(" | " . normstrlen($document['owner'], 20));
            cyan(" | " . normstrlen($document['confidentiality'], 15));
            cyan(" | " . normstrlen($document['class'], 30));
            cyan(" | " . normstrlen($document['folderUid'], 36));
            cyan(" | " . normstrlen($document['name'], 20));
            cyan(" | " . normstrlen(number_format($document['size'] / 1024, 2, ".", " ") . ' kB', 12));
            cyan(" |\n");
          }
        break;

        // case doc-create-random
        case 'doc-create-random': case 'dcr':
          $classes = [
            1 => "Actors.Persons",
            2 => "Actors.Teams",
            3 => "Assets.Tangibles.Parts",
          ];

          if (count($arguments) == 0) {
            foreach ($classes as $key => $class) {
              white("  {$key} = {$class}\n");
            }

            yellow("Select a class of the new document: ");
            $classIndex = (int) fgets($clih);

            yellow("What should be the size of the document (in bytes)? ");
            $fileSize = (int) fgets($clih);

          } else {
            $classIndex = (int) $arguments[0];

            $fileSize = strtolower(str_replace(' ', '', (str_replace(',', '.', $arguments[1] ?? '1024'))));
            if (substr($fileSize, -2) == 'gb') $fileSize = (float) $fileSize * 1024 * 1024 * 1024;
            elseif (substr($fileSize, -2) == 'mb') $fileSize = (float) $fileSize * 1024 * 1024;
            elseif (substr($fileSize, -2) == 'kb') $fileSize = (float) $fileSize * 1024;
          }

          if (isset($classes[$classIndex])) {
            $class = $classes[$classIndex];
            $confidentiality = rand(0, 9);

            green("Creating random document of class '{$class}', size {$fileSize} B and randomly chosen confidentiality {$confidentiality}.\n");

            $tmpRand = rand(1000, 9999);

            $api->createDocument('root', [
              'class' => $class,
              'confidentiality' => $confidentiality,
              'name' => 'rand-' . $tmpRand . '.txt',
              'content' => 'Hello world, random is ' . str_repeat($tmpRand . '. ', round($fileSize / 6)),
            ]);
            loglastrequest($api->lastRequest);

          } else {
            red("Unknown class.\n");
          }

        break;

        // rec-update
        case 'rec-update': case 'ru':
          $classes = [
            1 => "Actors.Persons",
            2 => "Actors.Teams",
            3 => "Assets.Tangibles.Parts",
          ];

          $confidentiality = rand(0, 9);

          if (count($arguments) == 0) {
            yellow("Enter UID of record to update: ");
            $recordUid = trim(fgets($clih));
          } else {
            $recordUid = $arguments[0];
          }

          green("Downloading original record content '{$recordUid}'.\n");
          $record = $api->getRecord($recordUid);
          loglastrequest($api->lastRequest);

          // Show available classes
          foreach ($classes as $key => $class) {
            white("  {$key} = {$class}\n");
          }

          yellow("Select a class to update the record with: ");
          $classIndex = (int) fgets($clih);

          if (isset($classes[$classIndex])) {
            $class = $classes[$classIndex];

            // Content creation
            yellow("Write a JSON string for the content to update the record with: ");
            $jsonString = (string) fgets($clih);
            $content = json_decode(trim($jsonString), true);

            if (isset($content)) {
              $newContent = [
                "class" => $class,
                "content" => $content,
                "confidentiality" => $confidentiality
              ];

              green("Updating record with random confidentiality {$confidentiality}.\n");
              $updatedRecord = $api->updateRecord($recordUid, $newContent);
              loglastrequest($api->lastRequest);

              // $tmp = json_decode($updatedDocument, true);
              // cyan("New version of the updated record is {$tmp['version']}.\n");
              var_dump($updatedRecord);
            } else {
              red("The content was empty or was not a valid JSON string.\n");
            }
          } else {
            red("Unknown class.\n");
          }

        break;

        // doc-get
        case 'doc-get': case 'dg':
          if (count($arguments) == 0) {
            yellow("Enter UID of document to download: ");
            $documentUid = trim(fgets($clih));
          } else {
            $documentUid = $arguments[0];
          }

          green("Getting document info '{$documentUid}'.\n");
          $document = $api->getDocument('root', $documentUid);
          loglastrequest($api->lastRequest);

          cyan("  UID = " . $document['uid'] . "\n");
          cyan("  Version = " . $document['version'] . "\n");
          cyan("  CreateTime = " . $document['createTime'] . "\n");
          cyan("  Author = " . $document['author'] . "\n");
          cyan("  Owner = " . $document['owner'] . "\n");
          cyan("  Class = " . $document['class'] . "\n");
          cyan("  Confidentiality = " . $document['confidentiality'] . "\n");
          cyan("  Folder UID = " . $document['folderUid'] . "\n");
          cyan("  Name = " . $document['name'] . "\n");
          cyan("  Size = " . $document['size'] . "\n");
        break;

        // doc-download
        case 'doc-download': case 'dd':
          if (count($arguments) == 0) {
            yellow("Enter UID of document to download: ");
            $documentUid = trim(fgets($clih));
          } else {
            $documentUid = $arguments[0];
          }

          green("Downloading document '{$documentUid}'.\n");
          $documentInfo = $api->getDocument('root', $documentUid);
          loglastrequest($api->lastRequest);
          $document = $api->downloadDocument('root', $documentUid);
          loglastrequest($api->lastRequest);

          if (is_dir($config['documentRoot'])) {
            $fileName = $config['documentRoot'] . '/'. $documentInfo['name'];
            file_put_contents($fileName, $document);

            green("Document was saved to '{$fileName}' (size: " . strlen($document) . ").\n");
          } else {
            red("Document was downloaded (size: " . strlen($document) . ") but not saved. Check 'documentRoot' configuration parameter.\n");
          }
        break;

        // doc-update
        case 'doc-download': case 'du':
          if (count($arguments) == 0) {
            yellow("Enter UID of document to update: ");
            $documentUid = trim(fgets($clih));
          } else {
            $documentUid = $arguments[0];
          }

          green("Downloading original document content '{$documentUid}'.\n");
          $content = $api->downloadDocument('root', $documentUid);
          loglastrequest($api->lastRequest);
          green("Randomly modifying document content.\n");

          $content = 'Update @ ' . date('Y-m-d H:i:s') . "\n" . $content;

          green("Updating document.\n");
          $updatedDocument = $api->updateDocument('root', $documentUid, $content);
          loglastrequest($api->lastRequest);

          $tmp = json_decode($updatedDocument, true);
          cyan("New version of the updated document is {$tmp['version']}.\n");
        break;

        // case exit
        case 'exit': case 'x':
          $exit = true;
        break;

        // default
        default:
          red("Don't know what to do.\n");
        break;
      }
    } catch (\Exception $e) {
      $error = @json_decode($e->getMessage(), true);
      red("!!! ERROR. Code: {$error['statusCode']}. Reason: {$error['reason']}\n");
      if (isset($error['responseBody'])) {
        if (is_string($error['responseBody'])) red("!!! {$error['responseBody']}\n");
        else if (isset($error['responseBody']['error'])) red("!!! {$error['responseBody']['error']}\n");
      }
    }
  }
} catch (\Exception $e) {
  red("!!! ERROR. {$e->getMessage()}\n");
}

white("\n");
white("Exiting.\n");
