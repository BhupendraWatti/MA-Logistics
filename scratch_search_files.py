import os

brain_dir = r"C:\Users\bhupe\.gemini\antigravity-cli\brain"
queries = ["193401", "162252"]

for root, dirs, files in os.walk(brain_dir):
    for f in files:
        if f.endswith(".log") or f.endswith(".txt") or f.endswith(".md"):
            path = os.path.join(root, f)
            try:
                with open(path, 'r', encoding='utf-8', errors='ignore') as file_obj:
                    content = file_obj.read()
                    for q in queries:
                        if q in content:
                            print(f"Found query '{q}' in file: {path}")
                            # Print a snippet of the context around it
                            idx = content.find(q)
                            start = max(0, idx - 300)
                            end = min(len(content), idx + 500)
                            print(f"--- Context ---\n{content[start:end]}\n---------------")
            except Exception as e:
                pass
