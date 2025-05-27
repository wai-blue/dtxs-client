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
      records = json.loads(client.getRecords())

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

  case "get-record":
    print(len(sys.argv))
    if (len(sys.argv) < 5): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py get-record <database> <recordUid>")
    else:
      recordUid = sys.argv[4]
      print("Getting record " + recordUid + " from database '" + database + "'.")
      client.database = database
      record = json.loads(client.getRecord(recordUid))

      if ('error' in record):
        print("ERROR: " + record['error'])
      else:
          print(
            " UID = " + record['uid']
            + " | Version = " + str(record['version'])
            + " | Owner = " + record['owner']
            + " | Class = " + record['class']
            + " | Content = " + str(record['content'])
          )

  case "get-record-history":
    if (len(sys.argv) < 5): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py get-record-history <database> <record-uid>")
    else:
      recordUid = sys.argv[4]
      print("Loading record history...")
      client.database = database
      records = json.loads(client.getRecordHistory(recordUid))

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

  case "create-record":
    if (len(sys.argv) < 7): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py create-record <database> <content-class> <content>")
    else:
      contentClass = sys.argv[4]
      content = json.loads(sys.argv[5]) #  "{\"name\": \"Alice\", \"age\": 30}"
      print("Creating a new record...")
      client.database = database
      recordUid = client.createRecord(contentClass, content)
      if ('error' in recordUid):
        error = json.loads(recordUid)
        print("ERROR: " + error['error'])
      else:
        print("Record " + recordUid + " was created.")

  case "update-record":
    if (len(sys.argv) < 7): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py update-record <database> <record-uid> <content-class> <content>")
    else:
      recordUid = sys.argv[4]
      contentClass = sys.argv[5]
      content = json.loads(sys.argv[6]) #  "{\"name\": \"Alice\", \"age\": 30}"
      print("Updating record...")
      client.database = database
      recordVersion = client.updateRecord(recordUid, contentClass, content)
      if ('error' in recordVersion):
        error = json.loads(recordVersion)
        print("ERROR: " + error['error'])
      else:
        print("Version " + recordVersion + " of the record was created.")

  case "delete-record":
    if (len(sys.argv) < 5): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py delete-record <database> <record-uid>")
    else:
      recordUid = sys.argv[4]
      print("Deleting record...")
      client.database = database
      response = client.deleteRecord(recordUid)
      if ('error' in response):
        error = json.loads(response)
        print("ERROR: " + error['error'])
      else:
        print("Record " + recordUid + " was succesfully deleted.")

  case "create-folder":
    if (len(sys.argv) < 5): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py list-documents <database> <folder-name> <parent-folder-name>")
    else:
      folderName = sys.argv[4]
      parentFolderName = sys.argv[5]
      client.database = database
      print("Creating folder...")
      contents = client.createFolder(folderName, parentFolderName)
      if ('error' in contents):
        error = json.loads(contents)
        print("ERROR: " + error['error'])
      else:
        print("Folder " + folderName + " was succesfully created.")

  case "get-folder-contents":
    if (len(sys.argv) < 4): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py get-folder-contents <database> <folder-uid>")
    else:
      folderUid = sys.argv[4]
      client.database = database
      print("Loading folder contents...")
      contents = json.loads(client.getFolderContents(folderUid))
      print(contents)
      if ('error' in contents):
        print("ERROR: " + contents['error'])
      else:
        folderInformation = contents['folder']
        parentFolderInformation = contents['parentFolder']
        print("--- FOLDER INFORMATION ---")
        print(
          " Folder Name = " + (folderInformation['folderName'] if folderInformation['folderName'] else "None")
          + " | Parent Folder Name = " + (parentFolderInformation['folderName'] if parentFolderInformation['folderName'] else "None")
        )

        print("--- SUBFOLDERS ---")
        for i, subFolder in enumerate(contents['subFolders']):
          print(f"UID = {subFolder['uid']} | Folder Name = {subFolder['folderName']} | Owner = {subFolder['owner']} | Parent Folder Name = {subFolder['parentFolderUid']}")
        print("--- DOCUMENTS ---")
        for i, document in enumerate(contents['documents']):
          print(f"UID = {document['uid']} | Version = {document['version']} | Owner = {document['owner']} | Class = {document['class']} | Size = {document['size']} | Checksum = {document['checksum']}"
          )

  case "list-documents":
    if (len(sys.argv) == 3): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py list-documents <database>")
    else:
      print("Getting records from database '" + database + "'.")
      client.database = database
      documents = json.loads(client.getDocuments())

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

  case "get-document":
    print(len(sys.argv))
    if (len(sys.argv) < 5): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py get-document <database> <folder-uid> <document-uid>")
    else:
      folderUid = sys.argv[4]
      documentUid = sys.argv[5]
      print("Getting document information...")
      client.database = database
      document = json.loads(client.getDocument(folderUid, documentUid))

      if ('error' in document):
        print("ERROR: " + document['error'])
      else:
          print(
            " UID = " + document['uid']
            + " | Version = " + str(document['version'])
            + " | Owner = " + document['owner']
            + " | Class = " + document['class']
            + " | Name = " + document['name']
            + " | Size = " + str(round(document['size'] / 1024 / 1024, 2)) + " MB"
          )

  case "upload-document":
    if (len(sys.argv) == 6):
      database = sys.argv[3]
      pathToFile = sys.argv[4]
      folderUid = sys.argv[5]

      client.database = database
      client.uploadDocument(pathToFile, folderUid, {
        'class': 'Actors.Persons',
        'confidentiality': 1,
        'name': os.path.basename(pathToFile)
      })
    else:
      print('Usage: test.py upload-document <database> <pathToFile>')

  case "delete-document":
    if (len(sys.argv) < 5): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py delete-document <database> <folder-uid> <document-uid>")
    else:
      folderUid = sys.argv[4]
      documentUid = sys.argv[5]
      print("Deleting documet...")
      client.database = database
      response = client.deleteDocument(folderUid, documentUid)
      if ('error' in response):
        error = json.loads(response)
        print("ERROR: " + error['error'])
      else:
        print("Document " + documentUid + " was succesfully deleted.")
