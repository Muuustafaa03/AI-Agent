FROM python:3.11-slim

WORKDIR /app
COPY . /app

# Install deps
RUN pip install --no-cache-dir -r requirements.txt

# Expose Flask port
EXPOSE 5001

CMD ["python", "main.py"]