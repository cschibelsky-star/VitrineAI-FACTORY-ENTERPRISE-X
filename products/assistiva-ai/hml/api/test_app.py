"""Synthetic data only. OAuth verification mocked; no production bypass exists."""
import os, tempfile, importlib, sqlite3
os.environ['DATA_DIR']=tempfile.mkdtemp(prefix='lucas-test-')
os.environ['GOOGLE_CLIENT_ID']='test-client.apps.googleusercontent.com'
import app as module
from fastapi.testclient import TestClient

origin={'Origin':module.ORIGIN}
def guardian(sub):
    client=TestClient(module.app,base_url=module.ORIGIN)
    module.id_token.verify_oauth2_token=lambda *a: {'sub':sub,'email':sub+'@example.test','name':'Synthetic guardian','email_verified':True,'iss':'https://accounts.google.com'}
    assert client.post('/api/auth/google',json={'credential':'x'*30},headers=origin).status_code==200
    csrf=client.get('/api/me').json()['csrf']
    return client,{**origin,'X-CSRF-Token':csrf}
def child(client,headers):
    r=client.post('/api/children',headers=headers,json={'guardian_name':'Synthetic guardian','relationship':'parent','child_name':'Synthetic child','legal_guardian':True})
    assert r.status_code==200
    return r.json()['id']
def test_auth_consent_receipt_isolation_revocation(monkeypatch):
    a,ha=guardian('a'); b,hb=guardian('b'); cid=child(a,ha)
    assert TestClient(module.app).get('/api/children/'+cid+'/history').status_code==401
    assert b.get('/api/children/'+cid+'/history').status_code==404
    assert a.post('/api/children/'+cid+'/consent',headers=origin,json={'owns_records':True,'authorize':True}).status_code==403
    assert a.post('/api/children/'+cid+'/pair',headers=ha,json={}).status_code==403
    assert a.post('/api/children/'+cid+'/consent',headers=ha,json={'owns_records':False,'authorize':True}).status_code==400
    assert a.post('/api/children/'+cid+'/consent',headers=ha,json={'owns_records':True,'authorize':True}).status_code==200
    code=a.post('/api/children/'+cid+'/pair',headers=ha,json={}).json()['code']
    device=TestClient(module.app,base_url=module.ORIGIN)
    response=device.post('/api/device/exchange',json={'code':code})
    assert response.status_code==200
    assert device.post('/api/device/exchange',json={'code':code}).status_code==401
    auth={'Authorization':'Bearer '+response.json()['token']}
    batch={'batch_id':'00000000-0000-4000-8000-000000000001','ownership_confirmed':True,'records':[{'type':'heart_rate','value':70,'start':'2026-01-01T10:00:00Z','end':'2026-01-01T10:00:00Z','origin':'synthetic.test'}]}
    assert device.post('/api/device/uploads',json=batch).status_code==401
    receipt=device.post('/api/device/uploads',headers=auth,json=batch)
    assert receipt.status_code==200 and receipt.json()['record_count']==1
    assert device.post('/api/device/uploads',headers=auth,json=batch).json()==receipt.json()
    batch['records'][0]['value']=71
    assert device.post('/api/device/uploads',headers=auth,json=batch).status_code==409
    rows=a.get('/api/children/'+cid+'/history').json()['items']
    assert len(rows)==1 and rows[0]['records'][0]['value']==70
    raw=(module.DATA/'health.sqlite').read_bytes()
    assert b'synthetic.test' not in raw and b'Synthetic child' not in raw
    assert a.post('/api/children/'+cid+'/revoke',headers=ha,json={}).status_code==200
    assert device.post('/api/device/uploads',headers=auth,json=batch).status_code==401
    assert len(a.get('/api/children/'+cid+'/history').json()['items'])==1

def test_invalid_google_and_cross_origin(monkeypatch):
    client=TestClient(module.app,base_url=module.ORIGIN)
    def invalid(*a): raise ValueError('invalid')
    monkeypatch.setattr(module.id_token,'verify_oauth2_token',invalid)
    assert client.post('/api/auth/google',json={'credential':'x'*30},headers=origin).status_code==401
    assert client.post('/api/auth/google',json={'credential':'x'*30},headers={'Origin':'https://other.test'}).status_code==403
    monkeypatch.setattr(module,'CLIENT_ID','')
    assert client.post('/api/auth/google',json={'credential':'x'*30},headers=origin).status_code==503
