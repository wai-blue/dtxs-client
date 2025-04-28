import requests
import json
import base64
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
  
  def __init__(self, config):
    # load configuration
    self.clientId = config['clientId']
    self.clientSecret = config['clientSecret']
    self.userName = config['userName']
    self.userPassword = config['userPassword']

    self.oauthEndpoint = config['oauthEndpoint']
    self.dtxsEndpoint = config['dtxsEndpoint']

    self.database = ''

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
      self.accessToken = response.json()['access_token'];
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

  def getDocuments(self):
    response = self.sendRequest(
      "POST", 
      "/database/" + self.database + "/documents",
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

    # receive chunkUid
    chunkUid = self.uploadDocumentChunk(folderUid, document['name'], '', 0, bytearray())
    print('Received chunkUid: ' + chunkUid + '.')

    # upload chunks
    chunkNumber = 1
    fileStats = os.stat(sourceFilePath)
    fileSize = fileStats.st_size
    chunkCount = ceil(fileSize / chunkSize)

    f = open(sourceFilePath, mode='rb')

    while True:
      chunk = f.read(chunkSize)

      if not chunk:
        break

      self.uploadDocumentChunk(folderUid, sourceFilePath, chunkUid, chunkNumber, chunk)
      print('Uploaded chunk #' + str(chunkNumber) + ' / ' + str(chunkCount) + '.')

      chunkNumber = chunkNumber + 1

    f.close()

    # merge chunks
    chunkUid = self.uploadDocumentChunk(folderUid, document['name'], chunkUid, -1, bytearray())
    print('Chunks merged.')

    # create document from merged chunks
    document['chunkUid'] = chunkUid
    self.createDocument(folderUid, document)
    print('Document created.')

    return chunkUid