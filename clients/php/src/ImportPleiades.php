<?php

/**
 * Script to import data from PLEIADES project
 * Author: Dusan Daniska, dusan.daniska@wai.blue
 * License: See LICENSE.md file in the root folder of the software package.
 */

namespace DtxsPhpClient;

class ImportPleiades {

  public Loader $client;

  public function __construct(Loader $client)
  {

    $this->client = $client;

  }

  /**
   * Reads content of the PLEIADES JSON file and converts it to DORADO data structure
   *
   * @return array DORADO-compatible data structure
   */
  public function loadRecordsFromJson(string $file): array
  {
    $pleiadesRecords = json_decode(file_get_contents($file), true);
    $doradoRecords = [];

    foreach ($pleiadesRecords as $pRecord) {
      $dContent = $pRecord['content'];
      unset($dContent['RecordInfo']);
      $dRecord = [
        'uid' => $pRecord['_id'],
        'class' => $pRecord['class'],
        'content' => $dContent,
      ];
      $doradoRecords[] = $dRecord;
    }

    return $doradoRecords;
  }

  /**
   * Saves DORADO-compatible data structure to selected DORADO database
   *
   * @return bool True if success.
   */
  public function saveRecords(string $database, array $records, int $confidentiality = 0): bool
  {
    $success = true;
    $this->client->setDatabase($database);
    foreach ($records as $record) {
      $this->client->createRecord([
        'uid' => $record['uid'],
        'class' => $record['class'],
        'confidentiality' => $confidentiality,
        'content' => $record['content'],
      ]);
    }
    return $success;
  }
}