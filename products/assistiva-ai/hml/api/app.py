"""Guardian-only HML. No health data in access logs, URLs, or public responses."""
import os, json, time, secrets, hashlib, sqlite3, threading
from pathlib import Path
from datetime import datetime, timezone
from contextlib import contextmanager
from collections import defaultdict, deque
from fastapi import FastAPI, Request, Response, HTTPException
from pydantic import BaseModel, Field, ConfigDict
from cryptography.fernet import Fernet
from google.oauth2 import id_token
from google.auth.transport.requests import Request as GoogleRequest

ORIGIN = os.getenv('PUBLIC_ORIGIN', 'https://lucas.hml.vitrineiapro.com.br')
CLIENT_ID = os.getenv('GOOGLE_CLIENT_ID', '').strip()
DATA = Path(os.getenv('DATA_DIR', '/data'))
DATA.mkdir(parents=True, exist_ok=True)
key_path = DATA / 'encryption.key'
if not key_path.exists():
    try:
        fd = os.open(key_path, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
        with os.fdopen(fd, 'wb') as f: f.write(Fernet.generate_key())
    except FileExistsError: pass
cipher = Fernet(key_path.read_bytes())
def enc(value): return cipher.encrypt(json.dumps(value, ensure_ascii=False).encode()).decode()
def dec(value): return json.loads(cipher.decrypt(value.encode()))
def digest(value): return hashlib.sha256(value.encode()).hexdigest()
def now(): return datetime.now(timezone.utc).isoformat()
@contextmanager
def db():
    conn = sqlite3.connect(DATA / 'health.sqlite', timeout=15)
    conn.row_factory = sqlite3.Row
    try:
        conn.execute('PRAGMA foreign_keys=ON')
        yield conn
        conn.commit()
    except Exception:
        conn.rollback(); raise
    finally: conn.close()
with db() as c:
    c.executescript('''
    CREATE TABLE IF NOT EXISTS guardians (id TEXT PRIMARY KEY, profile TEXT NOT NULL);
    CREATE TABLE IF NOT EXISTS sessions (hash TEXT PRIMARY KEY, guardian TEXT NOT NULL, csrf TEXT NOT NULL, expires REAL NOT NULL);
    CREATE TABLE IF NOT EXISTS children (id TEXT PRIMARY KEY, guardian TEXT NOT NULL, profile TEXT NOT NULL, consent TEXT, active INTEGER NOT NULL DEFAULT 0);
    CREATE TABLE IF NOT EXISTS pairs (hash TEXT PRIMARY KEY, child TEXT NOT NULL, expires REAL NOT NULL);
    CREATE TABLE IF NOT EXISTS devices (hash TEXT PRIMARY KEY, child TEXT NOT NULL, expires REAL NOT NULL);
    CREATE TABLE IF NOT EXISTS uploads (id TEXT PRIMARY KEY, child TEXT NOT NULL, batch TEXT NOT NULL, received TEXT NOT NULL, count INTEGER NOT NULL, payload TEXT NOT NULL, UNIQUE(child,batch));
    CREATE TABLE IF NOT EXISTS audit (id INTEGER PRIMARY KEY, guardian TEXT, child TEXT, event TEXT NOT NULL, at TEXT NOT NULL);
    ''')
app = FastAPI(docs_url=None, redoc_url=None, openapi_url=None)
limits = defaultdict(deque)
limit_lock = threading.Lock()
@app.middleware('http')
async def security(request, call_next):
    if request.method != 'GET' and request.headers.get('origin') not in (None, ORIGIN):
        return Response(status_code=403)
    if request.method != 'GET' and not request.url.path.startswith('/api/device/') and request.headers.get('origin') != ORIGIN:
        return Response(status_code=403)
    if int(request.headers.get('content-length', '0')) > 262144: return Response(status_code=413)
    if request.url.path in ('/api/auth/google','/api/device/exchange'):
        # Proxy overwrites X-Real-IP; backend is not exposed on a host port.
        key = (request.url.path, request.headers.get('x-real-ip', request.client.host))
        with limit_lock:
            q = limits[key]; t = time.time()
            while q and q[0] < t-600: q.popleft()
            if len(q) >= 20: return Response(status_code=429)
            q.append(t)
            if len(limits) > 10000:
                for k in list(limits):
                    if not limits[k] or limits[k][-1] < t-600: limits.pop(k, None)
    result = await call_next(request)
    result.headers['Cache-Control']='no-store'
    result.headers['X-Content-Type-Options']='nosniff'
    return result

def session(request):
    with db() as c:
        row = c.execute('SELECT * FROM sessions WHERE hash=? AND expires>?', (digest(request.cookies.get('lucas_session','')), time.time())).fetchone()
    if not row: raise HTTPException(401, 'Entre com Google para continuar.')
    if request.method != 'GET' and not secrets.compare_digest(request.headers.get('x-csrf-token',''), row['csrf']):
        raise HTTPException(403, 'Sessão inválida. Entre novamente.')
    return row

def owned(c, guardian, child):
    row = c.execute('SELECT * FROM children WHERE id=? AND guardian=?', (child,guardian)).fetchone()
    if not row: raise HTTPException(404, 'Cadastro não encontrado.')
    return row

def audit(c, guardian, child, event):
    c.execute('INSERT INTO audit(guardian,child,event,at) VALUES(?,?,?,?)', (guardian,child,event,now()))
class Strict(BaseModel): model_config=ConfigDict(extra='forbid')
class GoogleLogin(Strict): credential: str = Field(min_length=20,max_length=10000)
class Registration(Strict):
    guardian_name: str=Field(min_length=2,max_length=100)
    relationship: str=Field(min_length=2,max_length=50)
    child_name: str=Field(min_length=2,max_length=100)
    legal_guardian: bool
class Consent(Strict):
    owns_records: bool
    authorize: bool
class Code(Strict): code: str=Field(min_length=12,max_length=12,pattern='^[A-Fa-f0-9]{12}$')
class Record(Strict):
    type: str=Field(pattern='^(steps|heart_rate|sleep)$')
    value: float=Field(ge=0,le=1000000,allow_inf_nan=False)
    start: datetime
    end: datetime
    origin: str=Field(min_length=1,max_length=200)
class Upload(Strict):
    batch_id: str=Field(min_length=36,max_length=36,pattern='^[a-f0-9-]{36}$')
    ownership_confirmed: bool
    records: list[Record]=Field(min_length=1,max_length=100)

@app.get('/api/health')
def health(): return {'ok':True,'service':'projeto-lucas-api','google_configured':bool(CLIENT_ID)}
@app.get('/api/config')
def config(): return {'google_client_id':CLIENT_ID,'privacy_version':'2026-10-05-v1'}
@app.post('/api/auth/google')
def login(body:GoogleLogin, response:Response):
    if not CLIENT_ID: raise HTTPException(503,'Login Google ainda precisa ser configurado na HML.')
    try:
        info=id_token.verify_oauth2_token(body.credential,GoogleRequest(),CLIENT_ID)
        if not info.get('email_verified') or info.get('iss') not in ('accounts.google.com','https://accounts.google.com'): raise ValueError()
        guardian=digest(info['sub'])
    except Exception: raise HTTPException(401,'Não foi possível validar a conta Google.')
    token=secrets.token_urlsafe(32); csrf=secrets.token_urlsafe(32)
    with db() as c:
        c.execute('INSERT OR REPLACE INTO guardians VALUES(?,?)',(guardian,enc({'email':info['email'],'name':info.get('name','')})))
        c.execute('INSERT INTO sessions VALUES(?,?,?,?)',(digest(token),guardian,csrf,time.time()+3600))
        audit(c,guardian,None,'google_login')
    response.set_cookie('lucas_session',token,httponly=True,secure=True,samesite='strict',max_age=3600,path='/api')
    return {'ok':True}
@app.post('/api/logout')
def logout(request:Request,response:Response):
    s=session(request)
    with db() as c: c.execute('DELETE FROM sessions WHERE hash=?',(s['hash'],))
    response.delete_cookie('lucas_session',path='/api',secure=True,httponly=True,samesite='strict')
    return {'ok':True}
@app.get('/api/me')
def me(request:Request):
    s=session(request)
    with db() as c:
        profile=dec(c.execute('SELECT profile FROM guardians WHERE id=?',(s['guardian'],)).fetchone()['profile'])
        children=[{'id':r['id'],**dec(r['profile']),'authorized':bool(r['active'])} for r in c.execute('SELECT * FROM children WHERE guardian=?',(s['guardian'],))]
    return {'guardian':profile,'children':children,'csrf':s['csrf']}
@app.post('/api/children')
def register(body:Registration,request:Request):
    s=session(request)
    if not body.legal_guardian: raise HTTPException(400,'O cadastro exige confirmação do responsável legal.')
    child=secrets.token_hex(16)
    with db() as c:
        c.execute('INSERT INTO children(id,guardian,profile) VALUES(?,?,?)',(child,s['guardian'],enc(body.model_dump())))
        audit(c,s['guardian'],child,'child_registered')
    return {'id':child}
@app.post('/api/children/{child}/consent')
def consent(child:str,body:Consent,request:Request):
    s=session(request)
    if not body.owns_records or not body.authorize: raise HTTPException(400,'Confirme a titularidade dos registros e a autorização.')
    with db() as c:
        owned(c,s['guardian'],child)
        c.execute('UPDATE children SET consent=?,active=1 WHERE id=?',(enc({'at':now(),'version':'2026-10-05-v1','types':['steps','heart_rate','sleep'],'ownership':'selected_child','guardian':s['guardian']}),child))
        audit(c,s['guardian'],child,'consent_granted')
    return {'ok':True}
@app.post('/api/children/{child}/revoke')
def revoke(child:str,request:Request):
    s=session(request)
    with db() as c:
        owned(c,s['guardian'],child)
        c.execute('UPDATE children SET active=0 WHERE id=?',(child,))
        c.execute('DELETE FROM devices WHERE child=?',(child,)); c.execute('DELETE FROM pairs WHERE child=?',(child,))
        audit(c,s['guardian'],child,'consent_revoked')
    return {'ok':True}
@app.post('/api/children/{child}/pair')
def pair(child:str,request:Request):
    s=session(request)
    code=secrets.token_hex(6).upper()
    with db() as c:
        r=owned(c,s['guardian'],child)
        if not r['active']: raise HTTPException(403,'Autorize o envio primeiro.')
        c.execute('DELETE FROM pairs WHERE child=? OR expires<?',(child,time.time()))
        c.execute('INSERT INTO pairs VALUES(?,?,?)',(digest(code),child,time.time()+600))
        audit(c,s['guardian'],child,'pair_code_created')
    return {'code':code,'expires_in':600}
@app.post('/api/device/exchange')
def exchange(body:Code):
    token=secrets.token_urlsafe(32)
    with db() as c:
        c.execute('BEGIN IMMEDIATE')
        r=c.execute('SELECT p.*,c.profile,c.active,c.guardian FROM pairs p JOIN children c ON c.id=p.child WHERE p.hash=? AND p.expires>?',(digest(body.code.upper()),time.time())).fetchone()
        if not r or not r['active']: raise HTTPException(401,'Código inválido ou expirado. Gere outro na HML.')
        c.execute('DELETE FROM pairs WHERE hash=?',(r['hash'],))
        c.execute('INSERT INTO devices VALUES(?,?,?)',(digest(token),r['child'],time.time()+30*86400))
        audit(c,r['guardian'],r['child'],'device_linked')
    return {'token':token,'child_id':r['child'],'child_name':dec(r['profile'])['child_name'],'expires_in':30*86400}
@app.post('/api/device/uploads')
def upload(body:Upload,request:Request):
    auth=request.headers.get('authorization','')
    if not auth.startswith('Bearer '): raise HTTPException(401,'Vincule o aparelho primeiro.')
    if not body.ownership_confirmed: raise HTTPException(400,'Confirme de quem são os registros.')
    current=datetime.now(timezone.utc)
    for r in body.records:
        if r.start.tzinfo is None or r.end.tzinfo is None or r.end<r.start or r.end>current: raise HTTPException(400,'Horário do registro inválido.')
        if r.type=='heart_rate' and not 1<=r.value<=300: raise HTTPException(400,'Batimento inválido.')
        if r.type=='steps' and r.value!=int(r.value): raise HTTPException(400,'Passos inválidos.')
        if r.type=='sleep' and abs((r.end-r.start).total_seconds()/60-r.value)>1: raise HTTPException(400,'Duração de sono inválida.')
    payload=body.model_dump(mode='json')['records']
    with db() as c:
        c.execute('BEGIN IMMEDIATE')
        r=c.execute('SELECT d.child,c.active,c.guardian FROM devices d JOIN children c ON c.id=d.child WHERE d.hash=? AND d.expires>?',(digest(auth[7:]),time.time())).fetchone()
        if not r: raise HTTPException(401,'Vínculo expirado ou revogado. Vincule novamente.')
        if not r['active']: raise HTTPException(403,'Autorização revogada.')
        previous=c.execute('SELECT * FROM uploads WHERE child=? AND batch=?',(r['child'],body.batch_id)).fetchone()
        if previous:
            if dec(previous['payload'])!=payload: raise HTTPException(409,'Lote já recebido com conteúdo diferente.')
            receipt={'receipt_id':previous['id'],'received_at':previous['received'],'record_count':previous['count']}
        else:
            receipt={'receipt_id':secrets.token_hex(16),'received_at':now(),'record_count':len(payload)}
            c.execute('INSERT INTO uploads VALUES(?,?,?,?,?,?)',(receipt['receipt_id'],r['child'],body.batch_id,receipt['received_at'],len(payload),enc(payload)))
            audit(c,r['guardian'],r['child'],'health_received')
    return {'ok':True,**receipt}
@app.get('/api/children/{child}/history')
def history(child:str,request:Request):
    s=session(request)
    with db() as c:
        owned(c,s['guardian'],child)
        rows=c.execute('SELECT * FROM uploads WHERE child=? ORDER BY received DESC LIMIT 50',(child,)).fetchall()
    return {'items':[{'receipt_id':r['id'],'received_at':r['received'],'record_count':r['count'],'records':dec(r['payload'])} for r in rows]}
