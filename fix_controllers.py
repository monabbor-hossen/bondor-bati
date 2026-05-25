import os
import glob

for filepath in glob.glob("app/Controllers/*.php"):
    with open(filepath, 'r') as f:
        content = f.read()
    
    if 'public function __construct()' in content and 'parent::__construct();' not in content:
        content = content.replace('public function __construct() {', 'public function __construct() {\n        parent::__construct();')
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"Fixed {filepath}")
