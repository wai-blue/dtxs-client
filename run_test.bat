rem usage: run_test.bat <testName> <configFile> <dbName>
rem example: run_test.bat records config.local.json test-db
"C:\Users\Arthur Dent 42\AppData\Local\Programs\Python\Python311\python.exe" -m tests.test_%1.py %2 %3 %4 %5