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
import datetime
import sys
import json
import os

if (len(sys.argv) <= 2):
  print("  Usage: cli_client.py <configFile> <command> [arg1] [arg2] ...")
  print("  Available commands:")
  print("  --- DATABSES ---")
  print("  list-databases  Lists available databases.")
  print("")
  print("  --- RECORDS ---")
  print("  create-record <database> <recordClass> <content>               Create a new record.")
  print("  get-record <database> <recordUID>                              Retrieve the lastest information of a record.")
  print("  get-record-history <database> <recordUID>                      Retrieve the information of all versions of a record.")
  print("  list-records <database>                                        List records in given database.")
  print("  update-record <database> <recordUID> <recordClass> <content>   Update a record.")
  print("  delete-record <database> <recordUID>                           Delete a record.")
  print("")
  print("  --- FOLDERS ---")
  print("  create-folder <database> <folderName> <parentFolderUID>  Create a new folder.")
  print("  list-folders <database>                                  Get the list of all folders in a given databse.")
  print("  get-folder-contents <database> <folderUid>               Retrive the information about the folder, parent folder, subforders and documents in a folder.")
  print("  delete-folder <database> <folderUid>                     Delete a folder.")
  print("")
  print("  -- DOCUMENTS --")
  print("  upload-document <database> <pathToFile> <folderUid>   Upload a document to given database.")
  print("  get-document <database> <folderUid> <documentUid>     Retrieve the latest information of a document.")
  print("  list-documents <database>                             Lists documents in given database.")
  print("  delete-document <database> <folderUid> <documentUid>  Delete a document.")
  sys.exit()

configFile = sys.argv[1]
cmd = sys.argv[2]

with open(configFile) as f: config = json.load(f)

client = DtxsClient(config['dtxsClient'])
client.getAccessToken()

print("Received access token, length: " + str(len(client.accessToken)) + " bytes")

startTime = datetime.datetime.now()

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
    if (len(sys.argv) < 4): database = ''
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
      print("Usage: test.py get-record-history <database> <recorUid>")
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
    if (len(sys.argv) < 6): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py create-record <database> <recordClass> <content>")
    else:
      recordClass = sys.argv[4]
      content = json.loads(sys.argv[5]) #  '{\"name\": \"Alice\", \"age\": 30}'
      print("Creating a new record...")
      client.database = database
      recordUid = client.createRecord(recordClass, content)
      if ('error' in recordUid):
        error = json.loads(recordUid)
        print("ERROR: " + error['error'])
      else:
        print("Record " + recordUid + " was created.")

  case "update-record":
    if (len(sys.argv) < 7): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py update-record <database> <recordUid> <recordClass> <content>")
    else:
      recordUid = sys.argv[4]
      recordClass = sys.argv[5]
      content = json.loads(sys.argv[6]) #  '{\"name\": \"Alice\", \"age\": 30}'
      print("Updating record...")
      client.database = database
      recordVersion = client.updateRecord(recordUid, recordClass, content)
      if ('error' in recordVersion):
        error = json.loads(recordVersion)
        print("ERROR: " + error['error'])
      else:
        print("Version " + recordVersion + " of the record was created.")

  case "delete-record":
    if (len(sys.argv) < 5): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py delete-record <database> <recordUid>")
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
    if (len(sys.argv) < 6): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py create-folder <database> <folderName> <parentFolderUID>")
    else:
      folderName = sys.argv[4]
      parentFolderUid = sys.argv[5]
      client.database = database
      print("Creating folder...")
      response = client.createFolder(folderName, parentFolderUid)
      if ('error' in response):
        error = json.loads(response)
        print("ERROR: " + error['error'])
      else:
        print("Folder " + folderName + " " + response + " was succesfully created.")

  case "list-folders":
    if (len(sys.argv) < 4): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py list-folders <database>")
    else:
      client.database = database
      print("Listing all folders...")
      response = json.loads(client.getFolders())
      if ('error' in response):
        error = json.loads(response)
        print("ERROR: " + error['error'])
      else:
        for i, folder in enumerate(response):
          print(
            " UID = " + str(folder['uid'])
            + " | Folder Name = " + str(folder['folderName'])
            + " | Owner = " + folder['owner']
            + " | Confidentiality = " + str(folder['confidentiality'])
            + " | Parent folder UID = " + str(folder['parentFolderUid'])
          )

  case "get-folder-contents":
    if (len(sys.argv) < 4): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py get-folder-contents <database> <folderUid>")
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

  case "delete-folder":
    if (len(sys.argv) < 5): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py delete-folder <database> <folderUid>")
    else:
      folderUid = sys.argv[4]
      client.database = database
      print("Deleting folder...")
      response = client.deleteFolder(folderUid)
      if ('error' in response):
        error = json.loads(response)
        print("ERROR: " + error['error'])
      else:
        print("Folder was succesfully deleted.")

  case "list-documents":
    if (len(sys.argv) < 4): database = ''
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
    if (len(sys.argv) < 6): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py get-document <database> <folderUid> <documentUid>")
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
    if (len(sys.argv) < 7):
      database = sys.argv[3]
      pathToFile = sys.argv[4]
      folderUid = sys.argv[5]

      client.database = database
      print("Uploading document...")
      client.uploadDocument(pathToFile, folderUid, {
        'class': 'Actors.Persons',
        'confidentiality': 1,
        'name': os.path.basename(pathToFile)
      })
      print("Done!")
    else:
      print('Usage: test.py upload-document <database> <pathToFile> <folderUid>')

  case "delete-document":
    if (len(sys.argv) < 6): database = ''
    else: database = sys.argv[3]

    if (database == ''):
      print("Usage: test.py delete-document <database> <folderUid> <documentUid>")
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

endTime = datetime.datetime.now()

duration = endTime - startTime
print("Took " + str(duration.total_seconds()) + " seconds.")
