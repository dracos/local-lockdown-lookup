import csv
import json
import glob

out = {}
for f in glob.glob('ONSUD_OCT_2020_*.csv'):
    for row in csv.DictReader(open(f)):
        pc = row['pcds']
        la = row['lad19cd']
        out.setdefault(pc, set()).add(la)

out = {pc:list(las) for pc,las in out.items() if len(las) > 1}
out = json.dumps(out, sort_keys=True)
out = out.replace('],', '],\n ')
wr = open('split-postcodes.json', 'w')
wr.write(out)
wr.close()
