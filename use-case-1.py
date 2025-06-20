# This script executes activities described in
# use case #1 in DTXS documentaion on
# http://localhost/wai_blue/docs/dtxs-digital-twin-data-exchange-standard/api/use-cases/task-planning-risk-assessment-output-analysis

import os
import sys
import json
from clients.python.dtxs_client.main import DtxsClient

def prBlue(skk): print("\033[34m{}\033[00m" .format(skk))
def prRed(skk): print("\033[91m{}\033[00m" .format(skk))
def prGreen(skk): print("\033[92m{}\033[00m" .format(skk))
def prOrange(skk): print("\033[33m{}\033[00m" .format(skk))
def prYellow(skk): print("\033[93m{}\033[00m" .format(skk))
def prPurple(skk): print("\033[95m{}\033[00m" .format(skk))
def prCyan(skk): print("\033[96m{}\033[00m" .format(skk))
def prBlack(skk): print("\033[98m{}\033[00m" .format(skk))
def prLightGray(skk): print("\033[97m{}\033[00m" .format(skk))
def prLightBlue(skk): print("\033[94m{}\033[00m" .format(skk))
def prLightPurple(skk): print("\033[94m{}\033[00m" .format(skk))

prYellow("DTXS use case #1 test script")

if (len(sys.argv) <= 3):
  prYellow("Usage: python use-case-1.py <configFile> <dbName> <ifcModel>")
  prYellow("")
  prYellow("  configFile     Configuration of OAuth and DTXS endpoints")
  prYellow("  dbName         Name of the database where documents and records will be stored")
  prYellow("  ifcModel       Full path to an IFC model to use")
  sys.exit()

# Step 1: Prepare the environment

## Read this http://localhost/wai_blue/docs/dtxs-digital-twin-data-exchange-standard/api/use-cases/task-planning-risk-assessment-output-analysis/1-prepare-the-environment
## and check if you have your environment ready.

## Name of the database to be used will be provided as an argument

configFile = sys.argv[1]
dbName = sys.argv[2]
ifcModel = sys.argv[3]

with open(configFile) as f: config = json.load(f)

prBlue("[1] Checking the environment")

client = DtxsClient(config['dtxsClient'])
client.getAccessToken()

prLightBlue("  [1.1] Authenticating")

if (len(client.accessToken) == 0):
  prRed("    !! Did not receive access token. Exitting.")
  sys.exit()

prGreen("    -> Received acces token, length: " + str(len(client.accessToken)) + " bytes")

prLightBlue("  [1.2] Configuring DTXS client")

client.database = dbName
prGreen("    -> DTXS client configured")

prBlue("[2] Plan decommissioning task")

prLightBlue("  [2.1] Upload 3D model")
ifcModelDocumentUid = client.uploadDocument(ifcModel, "root", { 'class': 'Assets.Intangibles.Documents', 'name': os.path.basename(ifcModel) })
prGreen("    -> documentUid = " + ifcModelDocumentUid)

prLightBlue("  [2.2] Upload own metadata for the 3D model")
ifcModelMetadataRecordUid = client.recordUid = client.createRecord('Assets.Intangibles.Documents', {
  "Type": "IFC 3D model",
  "FileName": os.path.basename(ifcModel),
  "description": "3D model of the room where the robotic measurement task should be carried out.",
  "DocumentId": ifcModelDocumentUid
})
prGreen("    -> ifcModelMetadataRecordUid = " + ifcModelMetadataRecordUid)

prLightBlue("  [2.3] Create worker roles")
rpoRoleUid = client.recordUid = client.createRecord('Actors.Roles', { "Name": "Radiation protection expert" })
prGreen("    -> rpoRoleUid = " + rpoRoleUid)
operatorRoleUid = client.recordUid = client.createRecord('Actors.Roles', { "Name": "Robot operator" })
prGreen("    -> operatorRoleUid = " + operatorRoleUid)

prLightBlue("  [2.4] Add workers and robots")
workerJupiterUid = client.recordUid = client.createRecord('Actors.Persons', { "GivenName": "Jupiter", "FamilyName": "Jones" })
prGreen("    -> workerJupiterUid = " + workerJupiterUid)
workerPeterUid = client.recordUid = client.createRecord('Actors.Persons', { "GivenName": "Peter", "FamilyName": "Crenshaw" })
prGreen("    -> workerPeterUid = " + workerPeterUid)
robotHuskyUid = client.recordUid = client.createRecord('Actors.Robots', { "Name": "Husky A300", "ManufacturerName": "Clearpath" })
prGreen("    -> robotHuskyUid = " + robotHuskyUid)

prLightBlue("  [2.5] Create a team")
teamUid = client.recordUid = client.createRecord('Actors.Teams', {
  "Name": "Remote robotic measurement team",
  "MemberIds": [
    { "MemberType": "Actors.Persons", "MemberId": workerJupiterUid, "RoleId": rpoRoleUid },
    { "MemberType": "Actors.Persons", "MemberId": workerPeterUid, "RoleId": operatorRoleUid },
    { "MemberType": "Actors.Robots", "MemberId": robotHuskyUid }
  ]
})
prGreen("    -> teamUid = " + teamUid)

prLightBlue("  [2.6] Finaly, create the task")
taskUid = client.recordUid = client.createRecord('Actors.Tasks', {
  "Name": "Remote measurement",
  "Number": "T-001",
  "StartPlanned": "2025-07-01",
  "TeamId": teamUid,
  "DocumentIds": [ ifcModelMetadataRecordUid ]
})
prGreen("    -> taskUid = " + taskUid)