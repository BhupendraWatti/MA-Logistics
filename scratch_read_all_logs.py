import os

brain_dir = r"C:\Users\bhupe\.gemini\antigravity-cli\brain"
queries = ["193401", "162252"]

for root, dirs, files in os.walk(brain_dir):
    for f in files:
        if f.endswith(".log") or f.endswith(".jsonl") or f.endswith(".md"):
            path = os.path.join(root, f)
            try:
                with open(path, 'r', encoding='utf-8', errors='ignore') as file_obj:
                    content = file_obj.read()
                    for q in queries:
                        if q in content:
                            print(f"\nFound query '{q}' in: {path}")
                            lines = content.split("\n")
                            for line in lines:
                                if q in line:
                                    # Print only 200 chars around the query to be clean
                                    idx = line.find(q)
                                    start = max(0, idx - 100)
                                    end = min(len(line), idx + 200)
                                    print("  Match:", line[start:end])
            except Exception as e:
                pass
