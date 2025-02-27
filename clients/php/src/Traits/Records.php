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
   * @param  mixed $newRecordData New record's data.
   * @return string RecordUid in case of 200 success. Otherwise exception is thrown.
   */
  public function updateRecord(string $recordUid, array $newRecordData): string
  {
    $res = $this->sendRequest("PUT", "/database/{$this->database}/record/{$recordUid}", $newRecordData);
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
   * Shortcut to get record history
   *
   * @param  mixed $recordUid UID of the record to get.
   * @return array Data of the requested record. Otherwise exception is thrown.
   */
  public function getRecordHistory(string $recordUid): array
  {
    $res = $this->sendRequest("GET", "/database/{$this->database}/record/{$recordUid}/history");
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
  public function getRecords(string $query): array
  {
    $res = $this->sendRequest(
      "POST", 
      "/database/{$this->database}/records", 
      [
        "query" => $query,
      ]
    );

    return (array) json_decode((string) $res->getBody(), TRUE);
  }

}