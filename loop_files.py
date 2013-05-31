import json_to_mysql
import os

DIR = '/Users/DOT/Documents/data/'
# Loop saved files

for path in os.listdir(DIR):
    if path[0] == '.':
        continue

    json_to_mysql.json_to_mysql(DIR + path)
