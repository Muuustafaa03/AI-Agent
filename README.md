# AI Agent Project — Personal Research & Action Agent

A focused agent that ingests a URL or text, plans a short workflow, executes reliable skills (fetch, summarize, task-breakdown), stores all runs/steps, and outputs artifacts & KPIs. Includes a small ML component (priority/routing classifier) via a Python worker.

## Stack
- LAMP (PHP 8.2 + Apache, MySQL 8)
- Python worker (Flask) for ML
- Docker Compose (Windows)

## Quickstart (Docker)
1) Copy .env:  Copy-Item .env.example .env and set OPENAI_API_KEY.
2) docker-compose build && docker-compose up -d
3) Open http://localhost:8080
4) Create a run from the form.

## Roadmap
- Planner prompt & step-by-step trace UI
- KPIs: success rate, latency, $/run, user feedback, classifier accuracy
- ML: real /predict (features) and /train (batch)

# AI Agent 🤖 — Research & Action (LAMP + Python)

A full-stack, Dockerized **AI research assistant** that ingests URLs or raw text and generates:
- **Research Briefs**
- **Technical Summaries**
- **Task Breakdowns**
- **Topic Classification**
- **PRD Outlines**

It records **telemetry** (latency, tokens, model, cost), captures **artifacts**, and ships with a **dark-mode analytics dashboard** + CSV/JSON export.

---

## 🧭 Motivation / Problem Statement

**Problem.** Teams need quick, actionable understanding of long documents and webpages, but manual summarization is slow and error-prone. Existing tools often lack **traceability** (what ran, how long, how much it cost?) and **exportability** (can I track usage & cost over time?).

**Solution.** A minimal, transparent AI agent with:
- One-click **templated outputs** (briefs, PRDs, tasks)
- **Cost/latency/token** telemetry for each run
- **Artifacts** stored as JSON/TXT for reuse
- A built-in **Analytics** page (no extra infra)

---
