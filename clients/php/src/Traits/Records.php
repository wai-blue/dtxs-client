<?php

namespace DtxsPhpClient\Traits;

trait Records
{

  /**
   * Shortcut to create a record.
   *
   * @param  mixed $recordContent Content of the new record.
   * @return string RecordUid in case of 200 success. Otherwise exception is thrown.
   */
  public function createRecord(array $record): string
  {
    $res = $this->sendRequest("POST", "/database/{$this->database}/record", $record);
    return (string) $res->getBody();
  }
  
  /**
   * Shortcut to update a record
   *
   * @param  mixed $recordUid UID of the record to update.
   * @param  mixed $newContent New record's content.
   * @return string RecordUid in case of 200 success. Otherwise exception is thrown.
   */
  public function updateRecord(string $recordUid, array $newContent): string
  {
    $res = $this->sendRequest("PUT", "/database/{$this->database}/record/{$recordUid}", $newContent);
    return (string) $res->getBody();
  }
  
  /**
   * Shortcut to get a record.
   *
   * @param  mixed $recordUid UID of the record to get.
   * @return array Data of the requested record. Otherwise exception is thrown.
   */
  public function getRecord(string $recordUid, int $version = 0): array
  {
    $res = $this->sendRequest("GET", "/database/{$this->database}/record/{$recordUid}", ["version" => $version]);
    return (array) json_decode((string) $res->getBody(), TRUE);
  }
  
  /**
   * Shortcut to delete a record
   *
   * @param  mixed $recordUid UID of the record to delete.
   * @return string RecordUid in case of 200 success. Otherwise exception is thrown.
   */
  public function deleteRecord(string $recordUid): string
  {
    $res = $this->sendRequest("DELETE", "/database/{$this->database}/record/{$recordUid}");
    return (string) $res->getBody();
  }
  
  /**
   * Shortcut to get records by a query.
   *
   * @param  mixed $query A MongoDB-like search query.
   * @return array List of records matching the query.
   */
  public function getRecords($query = NULL, $fields = NULL, $methods = NULL): array
  {
    $res = $this->sendRequest(
      "POST", 
      "/database/{$this->database}/records", 
      [
        "query" => $query,
        "flieds" => $fields,
        "methods" => $methods
      ]
    );

    return (array) json_decode((string) $res->getBody(), TRUE);
  }

}