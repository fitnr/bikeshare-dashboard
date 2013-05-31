import sys
import json
import MySQLdb
from datetime import datetime
"""
one row looks like this:

"id":72,
"stationName":"W 52 St & 11 Av",
"availableDocks":28,
"totalDocks":39,
"latitude":40.76727216,
"longitude":-73.99392888,
"statusValue":"In Service",
"statusKey":1,
"availableBikes":7,
"stAddress1":"W 52 St & 11 Av",
"stAddress2":"",
"city":"","postalCode":"",
"location":"","altitude":"",
"testStation":false,
"lastCommunicationTime":null,
"landMark":""

station_status looks like:
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `availableDocks` smallint(5) unsigned DEFAULT NULL,
  `totalDocks` smallint(5) unsigned DEFAULT NULL,
  `statusValue` varchar(128) DEFAULT NULL,
  `statusKey` tinyint(1) DEFAULT NULL,
  `availableBikes` smallint(5) unsigned DEFAULT NULL,
  `lastCommunicationTime` datetime DEFAULT NULL,
  `stamp` datetime DEFAULT NULL,

stations looks like:
  `id` int(10) unsigned NOT NULL,
  `stationName` varchar(128) DEFAULT NULL,
  `latitude` double(10,8) DEFAULT NULL,
  `longitude` double(10,8) DEFAULT NULL,
  `stAddress1` varchar(256) DEFAULT NULL,
  `stAddress2` varchar(256) DEFAULT NULL,
  `city` varchar(128) DEFAULT NULL,
  `postalCode` varchar(128) DEFAULT NULL,
  `location` varchar(128) DEFAULT NULL,
  `altitude` int(11) DEFAULT NULL,
  `landMark` varchar(128) DEFAULT NULL,
  `communityboard` smallint(3) unsigned DEFAULT NULL,
"""

INSERT_ROW = """INSERT INTO station_status (station_id, availableDocks, totalDocks, statusValue, statusKey, availableBikes, lastCommunicationTime, stamp) VALUES ({id}, {availableBikes}, {totalDocks}, '{statusValue}', {statusKey}, {availableBikes}, {lastCommunicationTime}, '{stamp}')"""

INSERT_STATION = '''INSERT INTO stations (id, stationName, latitude, longitude, stAddress1, stAddress2, city, postalCode, location, altitude, landMark) VALUES ({id}, "{stationName}", {latitude}, {longitude}, "{stAddress1}", "{stAddress2}", '{city}', '{postalCode}', '{location}', {altitude}, '{landMark}')'''


def json_to_mysql(f):
    handle = open(f, 'rb')
    data = json.load(handle)

    stats = data['stationBeanList']
    timestamp = datetime.strptime(data['executionTime'], '%Y-%m-%d %H:%M:%S %p').strftime('%Y-%m-%d %H:%M:%S')

    conn = MySQLdb.connect(host='localhost', user='root', passwd="mysqlpass", db="wp")
    cursor = conn.cursor()

    # get list of all station_ids
    try:
        cursor.execute("SELECT id FROM stations")
    except Exception, e:
        print e

    result = cursor.fetchall()
    station_ids = set(x[0] for x in result)
    # print 'found stations:', len(station_ids)

    # Check for previous insertion!
    cursor.execute("SELECT COUNT(ID) FROM station_status WHERE stamp='{0}'".format(timestamp))
    if cursor.fetchall()[0][0] > 0:
        print 'skipping', timestamp
        return

    for row in stats:

        row['lastCommunicationTime'] = row['lastCommunicationTime'] if (row['lastCommunicationTime'] is not None) else 'NULL'
        row['stationName'] = row['stationName'].encode('ascii', 'ignore')
        row['stAddress1'] = row['stAddress1'].encode('ascii', 'ignore')

        try:
            cursor.execute(INSERT_ROW.format(stamp=timestamp, **row))
            # print "Auto Increment ID: {}".format(cursor.lastrowid)
        except Exception, e:
            print INSERT_ROW.format(stamp=timestamp, **row)

        if row['id'] not in station_ids:
            row['altitude'] = row['altitude'] if (row['altitude'] not in (None, '')) else 'NULL'
            try:
                cursor.execute(INSERT_STATION.format(**row))
                # print 'inserted station', row['id']
            except Exception, e:
                print 'problem inserting station', row['id']

    cursor.close()
    conn.commit()
    conn.close()


def main():
    json_to_mysql(sys.argv[1])

if __name__ == '__main__':
    main()
