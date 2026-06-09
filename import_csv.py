import csv
import subprocess
import os

csv_file = '/Users/mohammad/Downloads/Beekeeping.csv'
db_host = '127.0.0.1'
db_port = '8889'
db_user = 'root'
db_pass = 'root'
db_name = 'beekeeping'

# Read headers
with open(csv_file, 'r', encoding='utf-8', errors='replace') as f:
    reader = csv.reader(f)
    headers = next(reader)

table_name = "beekeeping_producers"
cols = []
for h in headers:
    # clean header
    h_clean = h.strip().replace(' ', '_').replace('/', '_').replace('-', '_').replace('(', '').replace(')', '').replace('.', '').replace('@', '').replace(',', '_').replace('?', '').replace('+', '')
    if not h_clean:
        h_clean = "col_" + str(len(cols))
    cols.append(f"`{h_clean}` TEXT")

create_stmt = f"CREATE DATABASE IF NOT EXISTS {db_name};\nUSE {db_name};\nDROP TABLE IF EXISTS {table_name};\nCREATE TABLE {table_name} ({', '.join(cols)});\n"

# write sql script to load data
load_stmt = f"""
USE {db_name};
LOAD DATA LOCAL INFILE '{csv_file}'
INTO TABLE {table_name}
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\\n'
IGNORE 1 ROWS;
"""

with open("import.sql", "w") as f:
    f.write(create_stmt)
    f.write(load_stmt)

# execute via mysql client
cmd = f"/Applications/MAMP/Library/bin/mysql -u {db_user} -p{db_pass} -h {db_host} -P {db_port} --local-infile=1 < import.sql"
print("Running command:", cmd)
res = os.system(cmd)
print("Result code:", res)
