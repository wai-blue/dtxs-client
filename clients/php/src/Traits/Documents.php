<?php

namespace DtxsPhpClient\Traits;

trait Documents
{

  /**
   * Shortcut to upload a document.
   *
   */
  public function uploadDocument(string $folderUid, string $fileName, string $chunkUid, int $chunkNumber, string $chunk): string
  {
    $res = $this->sendRequest("PATCH", "/database/{$this->database}/folder/{$folderUid}/document", [
      'chunkUid' => $chunkUid,
      'fileName' => $fileName,
      'chunk' => base64_encode($chunk),
      'chunkNumber' => $chunkNumber,
    ], true);
    return (string) $res->getBody();
  }

  /**
   * Shortcut to create a document.
   *
   * @param  mixed $folderUid UID of the folder where is document located.
   * @param  mixed $document Content of the new document.
   * @return string DocumentUid in case of 200 success. Otherwise exception is thrown.
   */
  public function createDocument(string $folderUid, array $document): string
  {
    $document['content'] = base64_encode($document['content'] ?? '');
    $res = $this->sendRequest("POST", "/database/{$this->database}/folder/{$folderUid}/document", $document, true);
    return (string) $res->getBody();
  }

  /**
   * Shortcut to create a document from existing file.
   *
   * @param  mixed $folderUid UID of the folder where is document located.
   * @param  mixed $uploadedFile Data about uploaded file, as it is stored in $_FILES.
   * @return string DocumentUid in case of 200 success. Otherwise exception is thrown.
   */
  public function createDocumentFromUploadedFile(string $folderUid, array $document, mixed $uploadedFile): string
  {
    $document['name'] = $uploadedFile['name'];
    $document['content'] = date('Y-m-d H:i:s');
    $res = $this->sendRequest("POST", "/database/{$this->database}/folder/{$folderUid}/document", $document, true);
    $documentUid = (string) $res->getBody();
    move_uploaded_file($uploadedFile['tmp_name'], $this->documentsStorageFolder . '/' . $documentUid . '---1');
    return (string) $res->getBody();
  }

  /**
   * Shortcut to update a document
   *
   * @param  mixed $folderUid UID of the folder where is document located.
   * @param  mixed $documentUid UID of the document to update.
   * @param  mixed $newContent New documents's content.
   * @return string Documents new version number in case of 200 success. Otherwise exception is thrown.
   */
  public function updateDocument(string $folderUid, string $documentUid, string $newContent): string
  {
    $res = $this->sendRequest("PUT", "/database/{$this->database}/folder/{$folderUid}/document/{$documentUid}", ['newContent' => base64_encode($newContent)]);
    return (string) $res->getBody();
  }

  /**
   * Get document's metadata
   *
   * @param  mixed $folderUid UID of the folder where is document located.
   * @param  mixed $documentUid UID of the document to update.
   * @return string DocumentUid in case of 200 success. Otherwise exception is thrown.
   */
  public function getDocument(string $folderUid, string $documentUid): array
  {
    $res = $this->sendRequest("GET", "/database/{$this->database}/folder/{$folderUid}/document/{$documentUid}");
    return json_decode((string) $res->getBody(), true);
  }

  /**
   * Download document
   *
   * @param  mixed $folderUid UID of the folder where is document located.
   * @param  mixed $documentUid UID of the document to update.
   * @return string Content of the document in case of 200 success. Otherwise exception is thrown.
   */
  public function downloadDocument(string $folderUid, string $documentUid, \Closure $onData): void
  {
    $res = $this->sendRequest(
      "GET",
      "/database/{$this->database}/folder/{$folderUid}/document/{$documentUid}/download",
      [],
      [
        \GuzzleHttp\RequestOptions::STREAM => true
      ],
    );

    $stream = $res->getBody();

    while (!$stream->eof()) {
      $chunk = (string) $stream->read(1024);
      $onData($chunk);
    }

    // return (string) $res->getBody();
  }

  /**
   * Shortcut to get document history
   *
   * @param  mixed $documentUid UID of the document to get.
   * @return array Data of the requested document. Otherwise exception is thrown.
   */
  public function getDocumentHistory(string $folderUid, string $documentUid): array
  {
    $res = $this->sendRequest("GET", "/database/{$this->database}/folder/{$folderUid}/document/{$documentUid}/history");
    return (array) json_decode((string) $res->getBody(), TRUE);
  }
  
  /**
   * Shortcut to delete a document
   *
   * @param  mixed $documentUid UID of the document to delete.
   * @return string DocumentUid in case of 200 success. Otherwise exception is thrown.
   */
  public function deleteDocument(string $folderUid, string $documentUid): string
  {
    $res = $this->sendRequest("DELETE", "/database/{$this->database}/folder/{$folderUid}/document/{$documentUid}");
    return (string) $res->getBody();
  }
  
  // /**
  //  * Shortcut to get documents by a query.
  //  *
  //  * @return array List of documents matching the query.
  //  */
  public function getDocuments(): array
  {
    $res = $this->sendRequest(
      "POST",
      "/database/{$this->database}/documents"
    );

    return (array) json_decode((string) $res->getBody(), TRUE);
  }

}