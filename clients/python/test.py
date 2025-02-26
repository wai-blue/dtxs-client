# DtxsClient python test script
# Author: dusan.daniska@wai.blue
# Usage: python test.py <command> [arg1] [arg2] ...
# Examples:
#   python test.py list-databases
#   python test.py list-records db_test_1
#   python test.py list-documents db_test_1

from dtxs_client.main import DtxsClient
import sys
import json

if (len(sys.argv) == 1):
  print("Usage: test.py <command> [arg1] [arg2] ...")
  print("Available commands:");
  print("  list-databases             Lists available databases.")
  print("  list-records <database>    Lists records in given database.")
  print("  list-documents <database>  Lists documents in given database.")
  sys.exit()

cmd = sys.argv[1]

config = {
  "clientId": "aquila",
  "clientSecret": "KkQYUGb0sR6BQslQRH0gMXTBWxTYphzL",
  "userName": "dusan.daniska",
  "userPassword": "dusan.daniska",
  "oauthEndpoint": "https://localhost:29084/realms/DORADO/protocol/openid-connect",
  "dtxsEndpoint": "http://localhost:23741/api/v0.04",
  "documentsStorageFolder": "q:\\workspace\\dorado"
}

client = DtxsClient(config)
client.getAccessToken()

print("Received acces token, length: " + str(len(client.accessToken)) + " bytes")

match cmd:
  case "list-databases":
    print("Getting list of databases.")
    databases = client.getDatabases()
    print("Available databases:")
    for i, database in enumerate(databases):
      print(" " + database['name'])

  case "list-records":
    if (len(sys.argv) == 2): database = ''
    else: database = sys.argv[2]

    if (database == ''):
      print("Usage: test.py list-records <database>")
    else:
      print("Getting records from database '" + database + "'.")
      client.database = database
      records = client.getRecords()

      if ('error' in records):
        print(records['error'])
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
    if (len(sys.argv) == 2): database = ''
    else: database = sys.argv[2]

    if (database == ''):
      print("Usage: test.py list-documents <database>")
    else:
      print("Getting records from database '" + database + "'.")
      client.database = database
      documents = client.getDocuments()

      if ('error' in documents):
        print(documents['error'])
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
