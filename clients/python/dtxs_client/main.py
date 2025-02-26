import requests
import json

class DtxsClient:
  clientId = '';               # CLIENT_ID defined in the IAM (Keycloak)
  clientSecret = '';           # CLIENT_SECRET defined in the IAM (Keycloak)
  userName = '';               # USER_NAME defined in the IAM (Keycloak)
  userPassword = '';           # USER_PASSWORD defined in the IAM (Keycloak)

  oauthEndpoint = '';          # OAuth compatible endpoint of the IAM
  dtxsEndpoint = '';           # DTXS endpoint

  documentsStorageFolder = ''; # Folder where documents are stored

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

    self.documentsStorageFolder = config['documentsStorageFolder']

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

    if (method == 'POST'):
      response = requests.post(self.dtxsEndpoint + command, headers=headers, data=body, verify=False).json()

    if (method == 'GET'):
      response = requests.get(self.dtxsEndpoint + command, headers=headers, data=body, verify=False).json()

    return response

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