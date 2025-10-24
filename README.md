# AI Agent 🤖 — Research & Action (LAMP + Python)

A full-stack, Dockerized **AI research assistant** that ingests URLs or raw text and generates:
- **Research Briefs**
- **Technical Summaries**
- **Task Breakdowns**
- **Topic Classifications**
- **PRD Outlines**

It records **telemetry** (latency, tokens, cost, model version), stores **artifacts**, and ships with a **dark-mode analytics dashboard** + CSV/JSON export.

---

## 🧭 Motivation / Problem Statement

**Problem.** Analysts, PMs, and researchers spend hours summarizing documents, tracking AI costs manually, and debugging LLM failures without visibility.  

**Solution.** A transparent, locally hostable AI agent that:
- Accepts URLs or text
- Outputs structured summaries instantly
- Tracks tokens, cost, latency, and model versions
- Stores editable artifacts in JSON/TXT
- Provides a built-in analytics dashboard — no external APM required

**Goal:** Deliver a *traceable, measurable, developer-first* AI summarization system.

---

## 🧱 Architecture Overview

**Three-service Docker stack:**

| Service | Purpose | Tech Stack |
|----------|----------|------------|
| **PHP (Apache)** | Web UI, routing, orchestration | PHP 8.2, Apache 2.4 |
| **Python Worker (Flask)** | LLM calls, telemetry, metrics | Python 3.11 |
| **MySQL** | Persistent DB for runs, artifacts, telemetry | MySQL 8.0 |

```mermaid
flowchart LR
  A[User] -->|Input: URL/Text| B[PHP Web UI]
  B -->|createRun()| C[(MySQL)]
  B -->|POST /summarize| D[Python Worker]
  D -->|LLM call + usage| D
  D -->|summary/tasks/prd + telemetry| B
  B -->|Store JSON/TXT artifact| E[(Storage /artifacts)]
  B -->|Update metrics| C
  B <-->|/analytics /errors| C

  subgraph Containers
    B
    D
    C
  end
```

🧪 Design Decisions & Trade-offs

| Decision                                     | Why                                                 | Trade-off                       |
| -------------------------------------------- | --------------------------------------------------- | ------------------------------- |
| **PHP/Python split**                         | PHP = simple orchestration, Python = strong AI SDKs | Requires Docker networking      |
| **Hybrid persistence (DB + files)**          | JSON/TXT are portable & inspectable                 | Must keep artifact paths synced |
| **Client-side Markdown preview (marked.js)** | Faster, safer rendering                             | Depends on browser JS           |
| **Chart.js**                                 | Lightweight, dark-mode ready                        | Less interactive than full BI   |
| **Local telemetry**                          | Zero vendor lock-in                                 | Manual aggregation              |
| **TailwindCSS**                              | Utility-first styling, quick dark mode              | Slightly verbose HTML classes   |

⚙️ Setup & Run (Local)
Prerequisites

Docker Desktop (Windows, Mac, or Linux)

Git

Steps
# 1. Clone repository
git clone https://github.com/<YOUR_USERNAME>/ai-agent.git
cd ai-agent

# 2. Start containers
docker-compose up -d

# 3. Access web UI
http://localhost:8080/

🌍 Environment Variables
# --- App ---
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# --- OpenAI ---
OPENAI_API_KEY=sk-REPLACE_ME

# --- Database ---
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=agentdb
DB_USERNAME=agentuser
DB_PASSWORD=agentpass

# --- Python Worker ---
PY_WORKER_URL=http://python:5001

🧰 Common Operations
# View container status
docker-compose ps

# View logs
docker logs agent_php --tail=50
docker logs agent_python --tail=50

# Restart stack
docker-compose down && docker-compose up -d

# Rebuild Python worker after edits
docker-compose build python && docker-compose up -d

📁 Project Structure
app/
  public/
    index.php
    landing.php
  src/
    Agent/Agent.php
    Controllers/
      RunController.php
      AnalyticsController.php
      ErrorsController.php
    Skills/Tools.php
    Util/db.php
    Util/env.php
python/
  main.py
sql/
  init.sql
storage/
  artifacts/
docker-compose.yml

🧩 Features

✅ URL & text ingestion
✅ Output templates (Research Brief, Technical Summary, Task Breakdown, PRD)
✅ Auto-generated titles from input
✅ Editable summaries with save-back
✅ Download summaries as .txt
✅ Dark Mode toggle
✅ Telemetry: latency, cost, tokens, model version
✅ Analytics dashboard with charts
✅ CSV/JSON export
✅ Error tracking page
✅ Run deletion with artifact cascade

📊 Metrics & Evaluation

| Metric                                | Description                            |
| ------------------------------------- | -------------------------------------- |
| `latency_ms`                          | Processing time per run                |
| `prompt_tokens` / `completion_tokens` | Token counts                           |
| `est_cost_usd`                        | Estimated API cost                     |
| `model_version`                       | OpenAI model used                      |
| `started_at` / `finished_at`          | Run duration                           |
| `status`                              | pending / running / succeeded / failed |

🧠 Lessons Learned

Observability early = debugging saved later

Editable output = real usability

Simplicity beats frameworks for small AI tools

Dark mode consistency prevents later UI thrash

Dockerized Python worker keeps dependencies isolated and predictable

🚀 Roadmap

 Async queue (Celery/Redis) for concurrent runs

 OAuth-based user runs

 Smart page caching for URLs

 Embedding-based semantic search

 Multi-model performance comparison in Analytics

 🧩 Architecture / System Design Summary

Data Flow:

User submits text or URL

PHP inserts new run in DB → calls Python worker

Worker fetches content → generates summary via OpenAI

Worker sends telemetry back (tokens, latency, cost)

PHP stores artifact file + metrics in MySQL

Dashboard visualizes results in real-time

🧮 Deployment Diagram

graph TB
  subgraph Docker_Network
  PHP[PHP / Apache Container] --> MySQL[(MySQL DB)]
  PHP --> Python[Python Worker Container]
  end
  User[Browser / Client] --> PHP
  PHP --> Storage[(Artifacts Volume)]

⚡ Performance Metrics

| Process           | Avg Time     | Notes                   |
| ----------------- | ------------ | ----------------------- |
| End-to-end run    | ~1.7 s       | Includes token counting |
| DB write latency  | < 20 ms      | Local MySQL             |
| Dashboard load    | < 2 s        | 50 recent runs          |
| Save-back latency | < 300 ms     | Editable summary        |
| Cost precision    | ±0.00001 USD | Based on OpenAI pricing |

🧩 Technical Decisions (Deep Dive)

LAMP baseline: simplicity and instant routing over framework complexity.

Dockerized isolation: consistent across dev, staging, prod.

OpenAI API: best trade-off between quality, reliability, and dev speed.

TailwindCSS: fast styling consistency, easy dark/light toggling.

Chart.js: small footprint, enough power for telemetry trends.

Local telemetry: avoids external APM costs, perfect for self-contained observability.

🧭 Motivation Recap

Goal: Create a measurable, interpretable, developer-first AI system.
Result: A locally hostable, full-stack, telemetry-enabled AI summarization agent with persistent outputs, analytics, and export tools — all open, simple, and portable.

🧩 Lessons & Trade-offs Summary

| Area            | Lesson                                | Trade-off                          |
| --------------- | ------------------------------------- | ---------------------------------- |
| Architecture    | Modular containers scale easily       | Requires networking setup          |
| UI              | Minimal JS, Tailwind-only works       | Less interactive                   |
| Observability   | Built-in telemetry                    | Manual aggregation                 |
| Maintainability | JSON/TXT artifacts are human-readable | Disk cleanup needed                |
| Deployability   | Docker makes hosting easy             | Slightly higher resource footprint |

📈 Example Analytics Snapshot

| Metric           | Value       |
| ---------------- | ----------- |
| **Total runs**   | 62          |
| **Success rate** | 96%         |
| **Avg latency**  | 1.7 s       |
| **Total cost**   | $0.48       |
| **Top model**    | GPT-4o-mini |
