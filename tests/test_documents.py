
import os
import sys
import json
from clients.python.dtxs_client.main import DtxsClient
from use_cases.common import prBlue, prRed, prGreen, prYellow, prLightBlue

if (len(sys.argv) <= 3):
  prYellow("Usage: python -m tests.test_documents <configFile> <dbName> <pathToDocument>")
  prYellow("")
  prYellow("  configFile         Configuration of OAuth and DTXS endpoints")
  prYellow("  dbName             Name of the database where records will be manipulated with")
  prYellow("  pathToDocument     A path to the document to be uploaded")
  sys.exit()

configFile = sys.argv[1]
dbName = sys.argv[2]
docPath = sys.argv[3]

with open(configFile) as f: config = json.load(f)

prBlue("[1] Checking the environment")

client = DtxsClient(config['dtxsClient'])
client.getAccessToken()

prLightBlue("Authenticating")

if (len(client.accessToken) == 0):
  prRed("!! Did not receive access token. Exitting.")
  sys.exit()

prGreen("-> Received access token, length: " + str(len(client.accessToken)) + " bytes")

prLightBlue("Configuring DTXS client")

client.database = dbName
prGreen("DTXS client configured")

# -------- DOCUMENT UPLOAD TEST --------
prYellow("Testing document upload:")
prBlue("[1.1] Uploading a document")
documentUid = client.uploadDocument(docPath, "root", { 'class': 'Assets.Intangibles.Documents', 'name': os.path.basename(docPath) })
print(documentUid)

prBlue("[1.2] Uploading a document with empty database name")
client.database = ''
res = client.uploadDocument(docPath, "root", { 'class': 'Assets.Intangibles.Documents', 'name': "Empty Database" })
print(res)

prBlue("[1.3] Uploading a document in a non-existing database")
client.database = 'xxx999'
res = client.uploadDocument(docPath, "root", { 'class': 'Assets.Intangibles.Documents', 'name': "Non-existing Database" })
print(res)

prBlue("[1.4] Uploading a document with empty class")
client.database = dbName
res = client.uploadDocument(docPath, "root", { 'class': '', 'name': "No Class" })
print(res)

prBlue("[1.5] Uploading a document with empty content")
res = client.uploadDocument(docPath, "root", {})
print(res)

prBlue("[1.6] Uploading a document with the wrong path to the file")
res = client.uploadDocument(".../abc/note.txt", "root", { 'class': 'Assets.Intangibles.Documents', 'name': "Wrong Path" })
print(res)

prBlue("[1.8] Uploading a document with no file path")
res = client.uploadDocument("", "root", { 'class': 'Assets.Intangibles.Documents', 'name': "No path" })
print(res)

prBlue("[1.9] Uploading a document with no folderUID")
res = client.uploadDocument(docPath, "", { 'class': 'Assets.Intangibles.Documents', 'name': "No folder" })
print(res)

prBlue("[1.10] Uploading a document with wrong folderUID")
res = client.uploadDocument(docPath, "xxx999", { 'class': 'Assets.Intangibles.Documents', 'name': "Wrong folder" })
print(res)

# -------- DOCUMENT READING TEST --------
prYellow("Testing document reading:")
prBlue("[2.1] Reading a document")
res = client.getDocument("root", documentUid) #TODO root folderUID wont be read for some reason
print(res)

prBlue("[2.2] Reading a document with no folderUID")
res =  client.getDocument("" , documentUid)
print(res)

prBlue("[2.3] Reading a document with wrong folderUID")
res =  client.getDocument("xxx9999" , documentUid)
print(res)

prBlue("[2.4] Reading a non-existent document")
res =  client.getDocument("root","xxx999")
print(res)

prBlue("[2.5] Getting a list of records")
res =  client.getDocuments()
print(res)

prBlue("[2.6] Reading a document from a non-existent database")
client.database = "xxx999"
res =  client.getDocument("root",documentUid)
print(res)

prBlue("[2.7] Reading a list of documents from a non-existent database")
res =  client.getDocuments()
print(res)

prBlue("[2.8] Reading a document from an empty database")
client.database = ""
res =  client.getDocument("root", documentUid)
print(res)

prBlue("[2.9] Reading a list of documents from an empty database")
res =  client.getDocuments()
print(res)

# # -------- DOCUMENT UPDATE TEST --------
prYellow("Testing document updating:")
prBlue("[3.1] Updating a document")
client.database = dbName
res =  client.updateDocument("root", documentUid, { 'class': 'New.Document.Class', 'name': os.path.basename(docPath)})
print(res)

prBlue("[3.2] Updating a non-existent document")
res =  client.updateDocument("root", "xxx9999", { 'class': 'New.Document.Class', 'name': os.path.basename(docPath)})
print(res)

prBlue("[3.3] Updating a document in a non-existent database")
client.database = "xxx999"
res =  client.updateDocument("root", documentUid, { 'class': 'New.Document.Class', 'name': os.path.basename(docPath)})
print(res)

prBlue("[3.4] Updating a document with an empty database name")
client.database = ""
res =  client.updateDocument("root", documentUid, { 'class': 'New.Document.Class', 'name': os.path.basename(docPath)})
print(res)

prBlue("[3.5] Updating a document with empty class")
client.database = dbName
res =  client.updateDocument("root", documentUid, { 'class': '', 'name': os.path.basename(docPath)})
print(res)

prBlue("[3.6] Updating a document with empty content")
res =  client.updateDocument("root", documentUid, {})
print(res)

prBlue("[3.7] Updating a document in an empty folder")
res =  client.updateDocument("", documentUid, { 'class': 'New.Document.Class', 'name': os.path.basename(docPath)})
print(res)

prBlue("[3.8] Updating a document in an wrong folder")
res =  client.updateDocument("xxx9999", documentUid, { 'class': 'New.Document.Class', 'name': os.path.basename(docPath)})
print(res)


# # -------- DOCUMENT DELETION TEST --------
prYellow("Testing document deletion:")
prBlue("[3.1] Deleting a document")
client.database = dbName
res =  client.deleteDocument("root",documentUid)

print(res)
prBlue("[3.2] Deleting a non-existent document")
res =  client.deleteDocument("root","xxx999")
print(res)

prBlue("[3.4] Deleting a document with empty database name")
client.database = ""
res =  client.deleteDocument("root","xxx999")
print(res)

prBlue("[3.7] Deleting a document with a non-existent database name")
client.database = "xxx999"
res =  client.deleteDocument("root","xxx999")
print(res)