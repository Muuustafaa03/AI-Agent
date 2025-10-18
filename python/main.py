from flask import Flask, request, jsonify
import joblib
from pathlib import Path

app = Flask(__name__)
MODEL_PATH = Path("/models/model_v1.pkl")

def load_model():
    if MODEL_PATH.exists():
        return joblib.load(MODEL_PATH)
    return None

@app.route("/predict", methods=["POST"])
def predict():
    # MVP: placeholder prediction
    return jsonify({"priority": "P3", "version": "v1"})

@app.route("/train", methods=["POST"])
def train():
    # MVP: training stub
    return jsonify({"status": "ok", "version": "v1"})

@app.route("/health", methods=["GET"])
def health():
    return jsonify({"ok": True})

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5001)