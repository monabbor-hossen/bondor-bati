import glob
import re

for filepath in glob.glob('/opt/lampp/htdocs/bondor-bati/app/Controllers/*.php'):
    with open(filepath, 'r') as f:
        content = f.read()
    
    # Remove private $db;
    content = re.sub(r'\s*private\s+\$db;\s*', '\n', content)
    
    # Remove the constructor
    content = re.sub(r'\s*public\s+function\s+__construct\(\)\s*\{\s*\$this->db\s*=\s*\(new\s*\\?Config\\Database\(\)\)->getConnection\(\);\s*\}\s*', '\n', content)
    
    with open(filepath, 'w') as f:
        f.write(content)
print("Done")
