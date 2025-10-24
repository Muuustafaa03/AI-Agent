import os, time
from flask import Flask, jsonify, request
from openai import OpenAI

app = Flask(__name__)

# ---------- Config ----------
MODEL_NAME = os.getenv("MODEL_NAME", "gpt-4o-mini")
OPENAI_API_KEY = os.getenv("OPENAI_API_KEY", "")
DAILY_TOKEN_CAP = int(os.getenv("MAX_DAILY_TOKENS", "200000"))
PER_RUN_TOKEN_CAP = int(os.getenv("MAX_TOKENS_PER_RUN", "8000"))

PRICE_PER_1K = {
    "gpt-4o-mini": {"input": 0.00015, "output": 0.00060},
}

client = OpenAI(api_key=OPENAI_API_KEY) if OPENAI_API_KEY else None

_daily_tokens = 0
_daily_reset_epoch = int(time.time() // 86400)
def _maybe_reset_daily():
    global _daily_tokens, _daily_reset_epoch
    cur = int(time.time() // 86400)
    if cur != _daily_reset_epoch:
        _daily_reset_epoch = cur
        _daily_tokens = 0

def _estimate_cost(model, prompt_tokens, completion_tokens):
    p = PRICE_PER_1K.get(model)
    if not p: return None
    return (prompt_tokens/1000.0)*p["input"] + (completion_tokens/1000.0)*p["output"]

@app.get("/health")
def health():
    return jsonify({"ok": True})

@app.post("/predict")
def predict():
    data = request.get_json(force=True, silent=True) or {}
    t0 = time.time()
    text = (data.get("text") or "")[:2000]
    prompt_tokens = max(10, len(text)//100)
    completion_tokens = 150
    latency_ms = int((time.time() - t0)*1000)
    return jsonify({
        "model": MODEL_NAME,
        "priority": "P3",
        "usage": {"prompt_tokens": prompt_tokens, "completion_tokens": completion_tokens},
        "est_cost_usd": _estimate_cost(MODEL_NAME, prompt_tokens, completion_tokens) or 0.0,
        "latency_ms": latency_ms
    })

# -------- Template Prompts --------
def build_prompts(template: str, text: str):
    base_system = "You are a precise analyst. Return clean Markdown. Be concise and faithful to the source."
    if template in ("research_brief", "brief"):
        user = f"""Summarize the content into a concise Research Brief with bullet points and short sections.

CONTENT:
{text}"""
        rtype = "summary"
    elif template in ("technical_summary", "technical"):
        user = f"""Produce a technical summary with sections (Overview, Key Details, Risks/Limitations, Open Questions).
Use bullet points where helpful.

CONTENT:
{text}"""
        rtype = "summary"
    elif template == "task_breakdown":
        user = f"""Extract a prioritized list of actionable tasks.
Return Markdown with a numbered list (max 15 items) and optional sub-bullets.

CONTENT:
{text}"""
        rtype = "tasks"
    elif template == "classify_topic":
        user = f"""Classify the dominant topic of the content into one short label (e.g., AI, Security, Product, Marketing).
Return two parts in Markdown:
- **Label**: <one-or-two-word label>
- **Rationale**: brief justification.

CONTENT:
{text}"""
        rtype = "classification"
    elif template == "prd_outline":
        user = f"""Draft a PRD outline as Markdown with sections:
- Title
- Problem Statement
- Goals / Non-Goals
- Users / Personas
- Requirements
- Success Metrics (KPIs)
- Risks & Assumptions
Use concise bullets. Base it strictly on the input.

CONTENT:
{text}"""
        rtype = "prd"
    else:
        user = f"Summarize this content in concise Markdown bullet points.\n\nCONTENT:\n{text}"
        rtype = "summary"

    return base_system, user, rtype

@app.post("/summarize")
def summarize():
    if client is None:
        return jsonify({"error": "OPENAI_API_KEY missing"}), 400

    data = request.get_json(force=True, silent=True) or {}
    text = (data.get("text") or "").strip()
    template = data.get("template") or data.get("style") or "research_brief"

    if not text:
        return jsonify({"error": "empty text"}), 400

    _maybe_reset_daily()
    global _daily_tokens
    if _daily_tokens >= DAILY_TOKEN_CAP:
        return jsonify({"error": "daily token cap reached"}), 429

    # Conservative length clamp (char-based)
    text = text[: PER_RUN_TOKEN_CAP * 5]

    system_prompt, user_prompt, rtype = build_prompts(template, text)

    t0 = time.time()
    try:
        resp = client.chat.completions.create(
            model=MODEL_NAME,
            messages=[
                {"role":"system","content":system_prompt},
                {"role":"user","content":user_prompt}
            ],
            temperature=0.2,
        )
        latency_ms = int((time.time() - t0)*1000)
        msg = resp.choices[0].message.content or ""
        usage = getattr(resp, "usage", None)
        pt = getattr(usage, "prompt_tokens", 0) if usage else 0
        ct = getattr(usage, "completion_tokens", 0) if usage else 0
        _daily_tokens += (pt + ct)
        est_cost = _estimate_cost(MODEL_NAME, pt, ct)

        # normalize payload by type
        payload = {"type": rtype, "model": MODEL_NAME,
                   "usage":{"prompt_tokens": pt, "completion_tokens": ct},
                   "est_cost_usd": est_cost, "latency_ms": latency_ms}

        if rtype == "tasks":
            payload["tasks_md"] = msg.strip()
        elif rtype == "classification":
            payload["classification_md"] = msg.strip()
        elif rtype == "prd":
            payload["prd_md"] = msg.strip()
        else:
            payload["summary"] = msg.strip()

        return jsonify(payload)
    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    import os
    port = int(os.environ.get('PORT', 5001))
    app.run(host='0.0.0.0', port=port, debug=False)
