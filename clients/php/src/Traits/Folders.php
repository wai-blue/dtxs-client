<?php

namespace DtxsPhpClient\Traits;

trait Folders
{

  /**
   * Shortcut to create a folder.
   *
   * @param  mixed $folderName Name of the new folder.
   * @return string FolderUid in case of 200 success. Otherwise exception is thrown.
   */
  public function createFolder(array $folder): string
  {
    $res = $this->sendRequest("POST", "/database/{$this->database}/folder", $folder);
    return (string) $res->getBody();
  }

  /**
   * Shortcut to rename a folder
   *
   * @param  mixed $folderUid UID of the folder to rename.
   * @param  mixed $newFolderName New folders's name.
   * @return string FolderUid in case of 200 success. Otherwise exception is thrown.
   */
  public function updateFolder(string $folderUid, string $newFolderName): string
  {
    $res = $this->sendRequest("PUT", "/database/{$this->database}/folder/{$folderUid}", ['newFolderName' => $newFolderName]);
    return (string) $res->getBody();
  }

  /**
   * Shortcut to delete a folder
   *
   * @param  mixed $folderUid UID of the folder to delete.
   * @param  mixed $newFolderName New folders's name.
   * @return string FolderUid in case of 200 success. Otherwise exception is thrown.
   */
  public function deleteFolder(string $folderUid): string
  {
    $res = $this->sendRequest("DELETE", "/database/{$this->database}/folder/{$folderUid}");
    return (string) $res->getBody();
  }

  /**
   * Shortcut to get metadata content of the folder (both sub-folders and documents).
   *
   * @return array Metadata and folder content.
   */
  public function getFolderContent(string $folderUid): array
  {
    $res = $this->sendRequest("GET", "/database/{$this->database}/folder/{$folderUid}");

    return (array) json_decode((string) $res->getBody(), TRUE);
  }

}