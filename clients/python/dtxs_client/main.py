import os
import requests
import json
import base64
import math
from pprint import pprint

class DtxsClient:
  clientId = '';               # CLIENT_ID defined in the IAM (Keycloak)
  clientSecret = '';           # CLIENT_SECRET defined in the IAM (Keycloak)
  userName = '';               # USER_NAME defined in the IAM (Keycloak)
  userPassword = '';           # USER_PASSWORD defined in the IAM (Keycloak)

  oauthEndpoint = '';          # OAuth compatible endpoint of the IAM
  dtxsEndpoint = '';           # DTXS endpoint

  accessToken = '';            # Access token received from IAM
  database = '';               # Name of the database which will be used

  debug = False

  def __init__(self, config):
    # load configuration
    self.clientId = config['clientId']
    self.clientSecret = config['clientSecret']
    self.userName = config['userName']
    self.userPassword = config['userPassword']

    self.oauthEndpoint = config['oauthEndpoint']
    self.dtxsEndpoint = config['dtxsEndpoint']

    self.database = ''

    if ('debug' in config):
      self.debug = config['debug']

    requests.packages.urllib3.disable_warnings()

  def getAccessToken(self):
    try:

      response = requests.post(
        self.oauthEndpoint + "/token",
        data={
          'grant_type': 'password',
          'client_id': self.clientId,
          'client_secret': self.clientSecret,
          'username': self.userName,
          'password': self.userPassword,
        },
        headers={"content-type": "application/x-www-form-urlencoded"},
        verify=False
      )
      self.accessToken = response.json()['access_token']
    except:
      print("Error while getting access token.")

    return self.accessToken

  def sendRequest(self, method, command, body):
    headers = {
      'content-type': 'application/json',
      'authorization': "Bearer " + self.accessToken,
    }

    response = {}
    bodyStr = json.dumps(body)

    if (method == 'POST'):
      response = requests.post(self.dtxsEndpoint + command, headers=headers, data=bodyStr, verify=False)

    if (method == 'GET'):
      response = requests.get(self.dtxsEndpoint + command, headers=headers, data=bodyStr, verify=False)

    if (method == 'PATCH'):
      response = requests.patch(self.dtxsEndpoint + command, headers=headers, data=bodyStr, verify=False)

    if (method == 'PUT'):
      response = requests.put(self.dtxsEndpoint + command, headers=headers, data=bodyStr, verify=False)

    if (method == 'DELETE'):
      response = requests.delete(self.dtxsEndpoint + command, headers=headers, data=bodyStr, verify=False)

    if (self.debug):
      print("  request: " + method + ", " + command + ", " + bodyStr)
      print("  response: " + response.text)

    return response.text

  def getDatabases(self):
    response = self.sendRequest('GET', '/databases', {})
    return response

  def getRecords(self):
    response = self.sendRequest(
      "POST",
      "/database/" + self.database + "/records",
      {}
    )
    return response

  def searchRecords(self, query):
    response = self.sendRequest(
      "POST",
      "/database/" + self.database + "/records",
      {"query": query}
    )
    return response

  def getRecord(self, recordUID):
    response = self.sendRequest(
      "GET",
      "/database/" + self.database + "/record/" + recordUID,
      {}
    )
    return response

  def getRecordHistory(self, recordUID):
    response = self.sendRequest(
      "GET",
      "/database/" + self.database + "/record/" + recordUID + "/history",
      {}
    )
    return response

  def createRecord(self, recordClass, content):
    response = self.sendRequest(
      "POST",
      "/database/" + self.database + "/record",
      {
        "class": recordClass,
        "content": content
      }
    )
    return response

  def updateRecord(self, recordUID, recordClass, content):
    response = self.sendRequest(
      "PUT",
      "/database/" + self.database + "/record/" + recordUID,
      {
        "class": recordClass,
        "content": content
      }
    )
    return response

  def deleteRecord(self, recordUID):
    response = self.sendRequest(
      "DELETE",
      "/database/" + self.database + "/record/" + recordUID,
      {}
    )
    return response

  def createFolder(self, folderName, parentFolderUid):
    response = self.sendRequest(
      "POST",
      "/database/" + self.database + "/folder",
      {
        "folderName": folderName,
        "parentFolderUid": parentFolderUid
      }
    )
    return response

  def getFolders(self):
    response = self.sendRequest(
      "GET",
      "/database/" + self.database + "/folders",
      {}
    )
    return response

  def getFolderContents(self, folderUid):
    response = self.sendRequest(
      "GET",
      "/database/" + self.database + "/folder/" + folderUid,
      {}
    )
    return response

  def deleteFolder(self, folderUid):
    response = self.sendRequest(
      "DELETE",
      "/database/" + self.database + "/folder/" + folderUid,
      {}
    )
    return response

  def getDocuments(self):
    response = self.sendRequest(
      "POST",
      "/database/" + self.database + "/documents",
      {}
    )
    return response

  def downloadDocument(self, folderUid, documentUid):
    response = self.sendRequest(
      "GET",
      "/database/" + self.database + "/folder/" + folderUid + "/document/" + documentUid + "/download",
      {}
    )
    return response

  def getDocument(self, folderUid, documentUid):
    response = self.sendRequest(
      "GET",
      "/database/" + self.database + "/folder/" + folderUid + "/document/" + documentUid,
      {}
    )
    return response

  def updateDocument(self, folderUID, documentUID, content):
    response = self.sendRequest(
      "PUT",
      "/database/" + self.database + "/folder/" + folderUID + "/document/" + documentUID,
      {
        "content": content
      }
    )
    return response


  def deleteDocument(self, folderUid, documentUid):
    response = self.sendRequest(
      "DELETE",
      "/database/" + self.database + "/folder/" + folderUid + "/document/" + documentUid,
      {}
    )
    return response

  def uploadDocumentChunk(self, folderUid, fileName, chunkUid, chunkNumber, chunk):
    response = self.sendRequest(
      "PATCH",
      "/database/" + self.database + "/folder/" + folderUid + "/documentChunk",
      {
        'chunkUid': chunkUid,
        'fileName': fileName,
        'chunk': base64.b64encode(chunk).decode('ascii'),
        'chunkNumber': chunkNumber,
      }
    )
    return response

  def createDocument(self, folderUid, document):
    if (hasattr(document, 'content')):
      document['content'] = base64.b64encode(document['content'].encode('utf-8'))

    response = self.sendRequest(
      "POST",
      "/database/" + self.database + "/folder/" + folderUid + "/document",
      document
    )

    return response

  def uploadDocument(self, sourceFilePath, folderUid, document):
    chunkSize = 1024*1024*25

    try:
      document["name"]
    except KeyError:
      return {"error":"Missing document name."}
    try:
      os.stat(sourceFilePath)
    except FileNotFoundError:
      return {"error":"File not found."}


    # receive chunkUid
    chunkUid = self.uploadDocumentChunk(folderUid, document['name'], '', 0, bytearray())

    if (self.debug):
      print('Received chunkUid: ' + chunkUid)

    # upload chunks
    chunkNumber = 1
    fileStats = os.stat(sourceFilePath)
    fileSize = fileStats.st_size
    chunkCount = math.ceil(fileSize / chunkSize)

    f = open(sourceFilePath, mode='rb')

    while True:
      chunk = f.read(chunkSize)

      if not chunk:
        break

      if (self.debug):
        print('Uploading chunk #' + str(chunkNumber) + ' / ' + str(chunkCount) + '.')

      self.uploadDocumentChunk(folderUid, sourceFilePath, chunkUid, chunkNumber, chunk)

      chunkNumber = chunkNumber + 1

    f.close()

    # merge chunks
    chunkUid = self.uploadDocumentChunk(folderUid, document['name'], chunkUid, -1, bytearray())

    if (self.debug):
      print('Chunks merged.')

    # create document from merged chunks
    document['chunkUid'] = chunkUid
    documentUid = self.createDocument(folderUid, document)

    if (self.debug):
      print('Document ' + document['name'] + ' created.')

    return documentUid

