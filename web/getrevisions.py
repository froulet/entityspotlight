from mw import api
import re
import json
import collections
import sys

compare = lambda x, y: collections.Counter(x) == collections.Counter(y)

session = api.Session("https://en.wikipedia.org/w/api.php", user_agent='Mozilla/5.0')

i=0

title = sys.argv[1]
start = sys.argv[2]
end = sys.argv[3]
limit = int(sys.argv[4])
offset = sys.argv[5]

if offset == "no":
    revisions = session.revisions.query(
         properties={'ids', 'content', 'timestamp', 'user', 'userid', 'tags', 'size'},
         titles={title},
         direction="newer",
         limit=limit,
         start=start,
         end=end
        #  parse= 'true'
     )

cache = []
gen = {}
allrevisions = []

for rev in revisions:
     i+=1
     lerev = {}
     #print('Timtestamp: ' + rev['timestamp'])
     #print (rev['*'])
     match = re.findall(r'\[\[Category.+\]\]', rev['*'])
# If-statement after search() tests if it succeeded
     categories = []
     for cat in match:
    # do something with each found email string

        cat=cat.replace("| ", "")
        cat=cat.replace("[", "")
        cat=cat.replace("]", "")
        cat=cat.replace(" ", "_")
        cat=cat[9:]
        categories.append(cat)

     if categories:
         if not compare(categories, cache):
             #print("Pas les mêmes")
             #print(categories)
             lerev['categories'] = categories
         cache=categories
     lerev['revid'] = rev['revid']
     lerev['user'] = rev['user']
     lerev['userid'] = rev['userid']
     lerev['timestamp'] = rev['timestamp']
     lerev['tag'] = rev['tags']
     lerev['size'] = rev['size']
     if 'minor' in rev:
         lerev['minor'] = rev['minor']
     allrevisions.append(lerev)

gen["revisions"] = allrevisions;
#gen["continue"] = '';
print(json.dumps(gen))
#print(i)
