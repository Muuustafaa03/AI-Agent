# AI Agent Project 2 — Personal Research & Action Agent

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