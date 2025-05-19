<?php

namespace DtxsPhpClient\Traits;

trait Streams
{

  public array $streams = [];

  /**
   * Open a real-time stream.
   *
   * @return string StreamUid in case of 200 success. Otherwise exception is thrown.
   */
  public function openStream(): string
  {
    $res = $this->sendRequest("POST", "/database/{$this->database}/stream/open", []);
    $resJson = (string) $res->getBody();

    $streamData = json_decode($resJson, true);
    $uid = $streamData['uid'] ?? '';
    $port = $streamData['port'] ?? 0;

    if ($port > 0 && $uid != '') {
      $fp = fsockopen("tcp://localhost", $port, $errno, $errstr, 30);

      $this->streams[$uid] = [
        'uid' => $uid,
        'port' => $port,
        'resource' => $fp,
      ];
    }

    return $uid;
  }

  /**
   * Get info about a stream
   *
   * @return string StreamUid in case of 200 success. Otherwise exception is thrown.
   */
  public function getStreamInfo(string $uid): array
  {
    return $this->streams[$uid] ?? [];
  }

  /**
   * Write data to a stream
   *
   * @return string StreamUid in case of 200 success. Otherwise exception is thrown.
   */
  public function writeStream(string $uid, string $data): void
  {
    if ($this->streams[$uid]) {
      fwrite($this->streams[$uid]['resource'], $data);
    }
  }

  /**
   * Close real-time stream.
   *
   * @return string StreamUid in case of 200 success. Otherwise exception is thrown.
   */
  public function closeStream(string $uid): string
  {
    if ($this->streams[$uid]) {
      fclose($this->streams[$uid]['resource']);

      $res = $this->sendRequest("POST", "/database/{$this->database}/stream/close", [
        'uid' => $uid,
        'port' => $this->streams[$uid]['port'],
      ]);

      return (string) $res->getBody();
    } else {
      return '';
    }
  }

}