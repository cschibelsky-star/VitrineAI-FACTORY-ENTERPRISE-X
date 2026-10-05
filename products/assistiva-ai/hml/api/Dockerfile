FROM python:3.12-slim
WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt && useradd -u 10001 -m lucas && mkdir /data && chown lucas /data
COPY app.py .
USER lucas
ENV DATA_DIR=/data
EXPOSE 8000
CMD ["uvicorn","app:app","--host","0.0.0.0","--port","8000","--no-access-log"]
