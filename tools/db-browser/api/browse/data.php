<?php

require __DIR__ . '/../../init.php';

function getOrderBy($order): array {
  $columns = [
    '_id',
    'class',
    'content',
    'recordInfo'
  ];

  $direction = $order[0]['dir'] == 'asc' ? 1 : -1;

  return [
    'sort' => $columns[$order[0]['column']],
    'direction' => $direction
  ];
}

checkIfClientIsLogged();

$api = new \SondixPhpClient\Client\Client(getApiConfig());
$api->getAccessToken();

$params = $_GET;

$api->setDatabase($params['database']);

$aggregate = false;
$search = (string) $params['search']['value'];
if (strlen($search) > 2) $aggregate = true;

$allFilteredRecords = $api->getRecords(
  NULL,
  NULL,
  [ 
    'aggregate' => $aggregate,
    'skip' => 0,
    'limit' => 0,
    'search' => addslashes($search),
  ]
);

$orderBy = getOrderBy($params['order']);

$filteredRecords = $api->getRecords(
  NULL,
  NULL,
  [
    'aggregate' => $aggregate,
    'skip' => (int) $params['start'],
    'limit' => (int) $params['length'],
    'sort' => [
      $orderBy['sort'] => $orderBy['direction']
    ],
    'search' => addslashes($search)
  ]
);

$filteredRecordsFormatted = [];
foreach ($filteredRecords as $recordKey => $record) {
  $tmpContent = $record['content'];

  unset($tmpContent['RecordInfo']);

  $filteredRecordsFormatted[$recordKey] = $record;

  $filteredRecordsFormatted[$recordKey]['content'] = formatRecordContentToHtml(
    $tmpContent, 
    $params['database'], 
    $api
  );

  $filteredRecordsFormatted[$recordKey]['recordInfo'] = json_encode(
    $record['content']['RecordInfo'],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
  );

  $filteredRecordsFormatted[$recordKey]['recordInfo'] = 
    trim($filteredRecordsFormatted[$recordKey]['recordInfo'], "{} ")
  ;

  $filteredRecordsFormatted[$recordKey]['recordInfo'] = 
    preg_replace('/\n    /', "\n", $filteredRecordsFormatted[$recordKey]['recordInfo'])
  ;

  $filteredRecordsFormatted[$recordKey]['editButton'] = 
    $record['class'] != "Database.Information" 
    ? "<a 
        href='edit.php?db={$params['database']}&_id={$record['_id']}&display-start={$params['start']}&page-length={$params['length']}' 
        class='btn btn-primary edit-button'
      >Edit</a>"
    : ""
  ;
}

echo json_encode([
  'start'           => $params['start'],
  'recordsTotal'    => count($filteredRecords),
  'recordsFiltered' => count($allFilteredRecords),
  'data'            => $filteredRecordsFormatted
]);