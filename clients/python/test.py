# DtxsClient python test script
# Author: dusan.daniska@wai.blue
# Usage: python test.py <configFile> <command> [arg1] [arg2] ...
# Examples:
#   python test.py dtxs-client-config.json list-databases
#   python test.py dtxs-client-config.json list-records db_test_1
#   python test.py dtxs-client-config.json list-documents db_test_1
#
# Sample config file contents:
# {
#   "dtxsClient": {
#     "clientId": "YOUR_CLIENT_ID",
#     "clientSecret": "YOUR_CLIENT_SECRET",
#     "userName": "YOUR_USERNAME",
#     "userPassword": "YOUR_USER_PASSWORD",
#     "oauthEndpoint": "https://dtxs-server.example.com/openid-connect",
#     "dtxsEndpoint": "http://dtxs-server.example.com/api/v0.04"
#   }
# }

from dtxs_client.main import DtxsClient
import sys
import json
import os

if (len(sys.argv) <= 2):
  print("Usage: test.py <configFile> <command> [arg1] [arg2] ...")
  print("Available commands:")
  print("  list-databases                          Lists available databases.")
  print("  list-records <database>                 Lists records in given database.")
  print("  list-documents <database>               Lists documents in given database.")
  print("  upload-document <database> <pathToFile> Upload a document to given database.")
  sys.exit()

configFile = sys.argv[1]
cmd = sys.argv[2]

with open(configFile) as f: config = json.load(f)

client = DtxsClient(config['dtxsClient'])
client.getAccessToken()

print("Received acces token, length: " + str(len(client.accessToken)) + " bytes")

match cmd:
  case "list-databases":
    print("Getting list of databases.")
    databases = client.getDatabases()
    if ('error' in databases):
      print("ERROR: " + databases)
    else:
      print("Available databases:")
      dbs = json.loads(databases)
      for i, db in enumerate(dbs):
        print(" " + db['name'])

  case "list-records":
    if (len(sys.argv) == 3): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py list-records <database>")
    else:
      print("Getting records from database '" + database + "'.")
      client.database = database
      records = client.getRecords()

      if ('error' in records):
        print("ERROR: " + records['error'])
      else:
        for i, record in enumerate(records):
          print(
            " UID = " + record['uid']
            + " | Version = " + str(record['version'])
            + " | Owner = " + record['owner']
            + " | Class = " + record['class']
            + " | Content = " + str(record['content'])
          )
          # print(" " + json.loads(record))

  case "list-documents":
    if (len(sys.argv) == 3): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py list-documents <database>")
    else:
      print("Getting records from database '" + database + "'.")
      client.database = database
      documents = client.getDocuments()

      if ('error' in documents):
        print("ERROR: " + documents['error'])
      else:
        for i, document in enumerate(documents):
          print(
            " UID = " + document['uid']
            + " | Version = " + str(document['version'])
            + " | Owner = " + document['owner']
            + " | Class = " + document['class']
            + " | Name = " + document['name']
            + " | Size = " + str(round(document['size'] / 1024 / 1024, 2)) + " MB"
          )

  case "upload-document":
    if (len(sys.argv) == 5):
      database = sys.argv[3]
      pathToFile = sys.argv[4]

      client.database = database
      client.uploadDocument(pathToFile, 'root', {
        'class': 'Actors.Persons',
        'confidentiality': 1,
        'name': os.path.basename(pathToFile)
      })
    else:
      print('Usage: test.py <database> <pathToFile>')

