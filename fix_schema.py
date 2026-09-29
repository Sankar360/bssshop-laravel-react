import re
import sys

path = sys.argv[1]
with open(path) as f:
    text = f.read()

lines = text.split("\n")
out = []
current_table = None
in_create_table = False

for line in lines:
    # Detect CREATE TABLE
    m = re.match(r'\s*CREATE TABLE "([^"]+)"', line)
    if m:
        current_table = m.group(1)
        in_create_table = True
        out.append(line)
        continue

    # Detect end of CREATE TABLE
    if in_create_table and line.strip().startswith(");"):
        in_create_table = False
        current_table = None
        out.append(line)
        continue

    # Only rewrite CONSTRAINT inside CREATE TABLE blocks
    if in_create_table and 'CONSTRAINT "' in line:
        line = re.sub(
            r'CONSTRAINT "([^"]+)"',
            lambda mm: f'CONSTRAINT "{current_table}_{mm.group(1)}"',
            line
        )
    out.append(line)

text = "\n".join(out)

# Rewrite CREATE INDEX (these are always top-level so safe to regex globally)
def fix_index(m):
    unique = m.group(1) or ""
    name = m.group(2)
    table = m.group(3)
    return f'CREATE {unique}INDEX "{table}_{name}" ON "{table}"'

text = re.sub(
    r'CREATE (UNIQUE )?INDEX "([^"]+)" ON "([^"]+)"',
    fix_index,
    text
)

with open(path, "w") as f:
    f.write(text)

print("done")
