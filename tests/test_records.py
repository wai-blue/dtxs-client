
import os
import sys
import json
from clients.python.dtxs_client.main import DtxsClient
from use_cases.common import prBlue, prRed, prGreen, prYellow, prLightBlue

if (len(sys.argv) <= 2):
  prYellow("Usage: python -m tests.test_record <configFile> <dbName>")
  prYellow("")
  prYellow("  configFile     Configuration of OAuth and DTXS endpoints")
  prYellow("  dbName         Name of the database where records will be manipulated with")
  sys.exit()

configFile = sys.argv[1]
dbName = sys.argv[2]

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

# -------- RECORD CREATION TEST --------
prYellow("Testing record creation:")
prBlue("[1.1] Creating a record")
recordUid = client.createRecord('Actors.Persons', { "GivenName": "Jupiter", "FamilyName": "Jones" })
print(recordUid)

prBlue("[1.2] Creating a record with empty database name")
client.database = ''
res = client.createRecord('Actors.Persons', { "Without": "Database" })
print(res)

prBlue("[1.3] Creating a record in a non-existing database")
client.database = 'xxx999'
res = client.createRecord('Actors.Persons', { "With": "Wrong Databse" })
print(res)

prBlue("[1.4] Creating a record with empty class")
client.database = dbName
res = client.createRecord('', {"Empty": "Class" })
print(res)

prBlue("[1.5] Creating a record with empty content")
res =  client.createRecord('Without.Content', {})
print(res)

prBlue("[1.6] Creating a record with the wrong format of the content")
res =  client.createRecord('Wrong.Format', "Wrong:Format")
print(res)

# -------- RECORD READING TEST --------
prYellow("Testing record reading:")
prBlue("[2.1] Reading a record")
res =  client.getRecord(recordUid)
print(res)

prBlue("[2.2] Reading a non-existent record")
res =  client.getRecord("xxx999")
print(res)

prBlue("[2.3] Getting a list of records")
res =  client.getRecords()
print(res)

prBlue("[2.3] Reading a record from a non-existent database")
client.database = "xxx999"
res =  client.getRecord(recordUid)
print(res)

prBlue("[2.4] Getting a list of records from a non-existent database")
res =  client.getRecords()
print(res)

prBlue("[2.3] Reading a record from an empty database")
client.database = ""
res =  client.getRecord(recordUid)
print(res)

prBlue("[2.4] Getting a list of records from an empty database")
res =  client.getRecords()
print(res)

# -------- RECORD UPDATE TEST --------
prYellow("Testing record updating:")
prBlue("[3.1] Updating a record")
client.database = dbName
res =  client.updateRecord(recordUid, 'Actors.Persons', { "GivenName": "Jef", "FamilyName": "Jones" })
print(res)

prBlue("[3.2] Updating a non-existent record")
res =  client.updateRecord("xxx999", 'Actors.Persons', { "GivenName": "Jef", "FamilyName": "Jones" })
print(res)

prBlue("[3.3] Updating a record in a non-existent database")
client.database = "xxx999"
res =  client.updateRecord("xxx999", 'Actors.Persons', { "GivenName": "Jef", "FamilyName": "Jones" })
print(res)

prBlue("[3.4] Updating a record with an empty database name")
client.database = ""
res =  client.updateRecord(recordUid, 'Actors.Persons', "GivenName:Jef")
print(res)

prBlue("[3.5] Updating a record with empty class")
client.database = dbName
res =  client.updateRecord(recordUid, '', { "GivenName": "Jef", "FamilyName": "Jones" })
print(res)

prBlue("[3.6] Updating a record with empty content")
res =  client.updateRecord(recordUid, 'Actors.Persons', {})
print(res)

prBlue("[3.7] Updating a record with the wrong format of the content")
res =  client.updateRecord(recordUid, 'Actors.Persons', "GivenName:Jef")
print(res)


# -------- RECORD DELETION TEST --------
prYellow("Testing record deletion:")
prBlue("[3.1] Deleting a record")
client.database = dbName
res =  client.deleteRecord(recordUid)
print(res)
prBlue("[3.2] Deleting a non-existent record")
res =  client.deleteRecord("xxx999")
print(res)
prBlue("[3.4] Deleting a record with empty database name")
client.database = ""
res =  client.deleteRecord(recordUid)
print(res)
prBlue("[3.7] Deleting a record with a non-existent database name")
client.database = "xxx999"
res =  client.deleteRecord(recordUid)
print(res)