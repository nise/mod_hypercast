import os
import json
import datetime


def humanize_time(millisecs):
    res = str(datetime.timedelta(milliseconds=millisecs))[:-3]
    if res[1:2] == ':':
        res = '0' + res
    return res
    
data_path = '../marks'



# 1. Iterate over directory
directory = os.fsencode(data_path)
for file in os.listdir(directory):
    filename = os.fsdecode(file)
    # 2. Take only json files
    if filename.endswith(".json"):
        file_full_path = os.path.join(data_path,filename)
        # 3. Open json file
        with open(file_full_path, encoding='utf-8', errors='ignore') as json_data:
            data_in_file = json.load(json_data, strict=False)
            # 4. Iterate over objects and print relevant fields
            output = 'WEBVTT\n\n'
            item_count = 0
            for json_object in data_in_file:
                # print("time: %s, type: %s, value: %s" %
                #      (humanize_time(json_object['time']), json_object['type'], json_object['value']))
                if json_object['type']=='ssml':
                    #1
                    #00:00:01.903 --> 00:00:16.564
                    #{"name":"ABC","id":3,"x":73,"y":16}
                    output += str(item_count) + '\n'
                    output += humanize_time(json_object['time']) + ' --> ' + humanize_time(json_object['time']+10) + '\n'
                    output += ''.join([
                        '{"time":',
                        str(json_object['time']),
                        ',',
                        '"value":"',
                        str(json_object['value']),
                        '"}',
                        '\n'
                    ])
                    output += '\n' # empty line
                    item_count += 1
            print(output)
            f = open(os.path.join('..', 'vtt', filename[:-4] + 'vtt'), "w")
            f.write(output)
            f.close()
                    



                
