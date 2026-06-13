import json

log_file = r"C:\Users\bhupe\.gemini\antigravity-cli\brain\76a63a8e-8e5d-478c-b5a0-d30844f24b36\.system_generated\logs\transcript.jsonl"
queries = ["193401", "162252"]

try:
    with open(log_file, 'r', encoding='utf-8') as f:
        for line in f:
            for q in queries:
                if q in line:
                    data = json.loads(line)
                    print(f"Match for {q} (Step {data.get('step_index')}):")
                    if "content" in data:
                        print(data["content"][:800])
                    print("-" * 50)
except Exception as e:
    print("Error:", e)
