#!/usr/bin/env python3
"""Auditoria somente-leitura de exportação phpMyAdmin. Não faz mudanças no banco."""
import sys,re,json,csv,sqlite3,collections,hashlib,unicodedata,pathlib,statistics,urllib.parse
from pathlib import Path

ROOT=Path(__file__).resolve().parent
SQL=Path(sys.argv[1]) if len(sys.argv)>1 else Path('/mnt/data/mat_temporis_curadoria.sql')
SQL_TEXT=SQL.read_text(encoding='utf-8-sig')
SNAPSHOT_SHA256=hashlib.sha256(SQL.read_bytes()).hexdigest()
PATTERN=re.compile(r'^INSERT INTO `([^`]+)` \((.*?)\) VALUES$', re.M)

def fields(s):
    return re.findall(r'`([^`]+)`',s)

def parse_row(s):
    # Each row is on one line in phpMyAdmin dump. Handles MySQL \\ escapes and doubled apostrophes.
    s=s.strip()
    assert s.startswith('('), s[:30]
    result=[];pos=1;N=len(s)
    while pos<N:
        while pos<N and s[pos].isspace(): pos+=1
        if s[pos]=="'":
            pos+=1;buf=[]
            while pos<N:
                ch=s[pos]
                if ch=='\\':
                    pos+=1
                    if pos>=N: raise ValueError('escape end')
                    escape={'0':'\x00','n':'\n','r':'\r','t':'\t','b':'\b','Z':'\x1a','\\':'\\',"'":"'",'"':'"'}
                    buf.append(escape.get(s[pos],s[pos]));pos+=1
                elif ch=="'":
                    if pos+1<N and s[pos+1]=="'": buf.append("'");pos+=2
                    else: pos+=1;break
                else: buf.append(ch);pos+=1
            value=''.join(buf)
        else:
            start=pos
            while pos<N and s[pos] not in ',)': pos+=1
            raw=s[start:pos].strip()
            if raw.upper()=='NULL': value=None
            elif re.fullmatch(r'-?\d+',raw): value=int(raw)
            elif re.fullmatch(r'-?\d+\.\d+(?:[eE][+-]?\d+)?',raw):value=float(raw)
            else: raise ValueError(f'Unparsed: {raw[:90]!r}')
        result.append(value)
        while pos<N and s[pos].isspace(): pos+=1
        if pos>=N: raise ValueError(f'missing close row {s[-40:]!r}')
        if s[pos]==',': pos+=1;continue
        if s[pos]==')': pos+=1;break
        raise ValueError(f'bad delim {pos} {s[pos:pos+20]!r}')
    assert s[pos:].strip() in {',',';', ''},repr(s[pos:pos+20])
    return result

def parse_dump():
    data=collections.defaultdict(list); parsed=0
    names={}
    lines=SQL_TEXT.splitlines()
    for num,line in enumerate(lines,1):
        m=re.match(r'^INSERT INTO `([^`]+)` \((.*?)\) VALUES$',line)
        if m:
            tab=m.group(1);names[tab]=fields(m.group(2));continue
        if line.startswith('(') and line.endswith(('),',');')) and names:
            if not tab:raise ValueError(f'no target for {num}')
            row=parse_row(line)
            if len(row)!=len(names[tab]):raise ValueError(f'columns != row {tab} {num} {len(names[tab])} {len(row)}')
            data[tab].append(dict(zip(names[tab],row)))
            parsed+=1
    return data

DATA=parse_dump()
C=DATA['cur_candidatos'];K=DATA['cur_criterios'];S=DATA['cur_fontes'];L=DATA['cur_log'];R=DATA['cur_rodadas']
byid={c['id_extracao']:c for c in C}
cs=collections.defaultdict(list); fs=collections.defaultdict(list)
for k in K:cs[k['id_extracao']].append(k)
for f in S:fs[f['id_extracao']].append(f)

def jload(x,default):
    try: return json.loads(x) if x else default
    except Exception:return default

def scrub(s):
    s=unicodedata.normalize('NFKD',s.lower())
    return ' '.join(''.join(c for c in s if not unicodedata.combining(c) and (c.isalnum() or c==' ')).split())

def issue(id_,type_,severity,detail):
    issues.append({'ID':id_,'Tipo':type_,'Gravidade':severity,'Detalhe':detail})

issues=[]; rows=[]; refs_err=[]; false_date=[]; ambiguous_year=[]
all_codes=sorted({k['codigo'] for k in K})
for c in C:
    id_=c['id_extracao'];ks=cs[id_];ss=fs[id_]
    src_ids={s['fonte_id'] for s in ss}
    notes={k['codigo'] for k in ks if k['nota'] is not None and k['situacao']!='SEM_EVIDENCIA'}
    all_rows=len(ks)
    non_null_note=sum(k['nota'] is not None for k in ks)
    missing_codes=sorted(set(all_codes)-{k['codigo'] for k in ks})
    no_sources=0;invalid_refs=[];unknown_refs=[];note_missing_refs=0; sus_notes=[]
    for k in ks:
        src=jload(k['fontes_ids_json'],[])
        if not isinstance(src,list):issue(id_,'FONTE_FORMATO','alto',k['codigo']+' fontes_ids_json não é lista');src=[]
        for code in src:
            if code not in src_ids:
                invalid_refs.append((k['codigo'],str(code)))
        if k['nota'] is not None:
            if not src:note_missing_refs+=1
            if k['nota'] < 0 or k['nota']>5: sus_notes.append(k['codigo']+':'+str(k['nota']))
            if k['situacao']=='SEM_EVIDENCIA': issue(id_,'NOTA_SEM_EVIDENCIA','alto',k['codigo']+' tem nota com situacao SEM_EVIDENCIA')
    for code,source in invalid_refs: issue(id_,'FONTE_INEXISTENTE','alto',f'{code} cita fonte_id {source} inexistente')
    if note_missing_refs: issue(id_,'NOTA_SEM_FONTE','alto',f'{note_missing_refs} critério(s) pontuado(s) sem vínculo com fontes')
    if sus_notes: issue(id_,'NOTA_FORA_FAIXA','alto',', '.join(sus_notes))
    if missing_codes:issue(id_,'CRITERIO_AUSENTE','medio','Códigos sem registro: '+','.join(missing_codes))
    if c['ano_validado'] is None: issue(id_,'SEM_ANO_VALIDADO','medio','Data precisa de conferência ou intervalo histórico')
    if c['ano_validado']==0:issue(id_,'ANO_ZERO','alto','Ano 0 não existe na cronologia histórica convencional')
    if c['ano_validado'] is not None and c['ano_validado']>2026:issue(id_,'ANO_FUTURO','alto','Ano validado além de 2026')
    p=jload(c['pendencias_json'],[])
    if not isinstance(p,list):issue(id_,'PENDENCIAS_JSON','alto','Pendências não são array JSON');p=[]
    dup=jload(c['duplicatas_json'],[])
    if not isinstance(dup,list):issue(id_,'DUPLICATAS_JSON','alto','Duplicatas não são array JSON');dup=[]
    for d in dup:
        if isinstance(d,str) and re.fullmatch('MCD-\\d{5}',d) and d not in byid:issue(id_,'DUPLICATA_ID_AUSENTE','medio','Referência '+d+' não existe no dump')
    if c['homologado'] and c['estado_revisao_humana']!='APROVADO':issue(id_,'HOMOLOGADO_NAO_APROVADO','critico','Homologado com estado diferente de APROVADO')
    if c['estado_revisao_humana']=='APROVADO' and (p or len(notes)<11):issue(id_,'APROVADO_COM_BLOQUEIOS','alto',f'Aprovado com {len(p)} pendências; {len(notes)} notas')
    if len(ks)!=len(all_codes):issue(id_,'CRITERIOS_QUANTIDADE','medio',f'{len(ks)} de {len(all_codes)} rubricas')
    external=[f for f in ss if f['fonte_id']!='B2006' and (f['url'] or f['natureza']!='LIVRO_BASE_DOCUMENTAL')]
    exturls=[f['url'] for f in external if f['url']]
    badurls=[u for u in exturls if not urllib.parse.urlparse(u).scheme.startswith('http')]
    if badurls:issue(id_,'URL_INVALIDA','medio','URL sem esquema HTTP(S)')
    if c['categoria'] in ('CONTEXTO_HISTORICO','ESCOPO_DUVIDOSO','POSSIVEL_DUPLICATA'):
        screening='TRIAR_ESCOPO' if c['categoria']!='POSSIVEL_DUPLICATA' else 'TRIAR_DUPLICATA'
    elif c['situacao_identificacao'] in ('CONTEXTO_NAO_MARCO','ESCOPO_A_CONFIRMAR','POSSIVEL_DUPLICATA'):
        screening='TRIAR_ESCOPO'
    elif non_null_note==11 and c['ano_validado'] is not None and not invalid_refs and len(external)>0:
        screening='AUDITAR_PRIORITARIO'
    elif non_null_note==11:
        screening='CONFERIR_DATA_FONTES'
    elif non_null_note>=8:
        screening='COMPLETAR_POUCAS_NOTAS'
    elif non_null_note>0:
        screening='PESQUISAR_NOTAS'
    else:
        screening='INICIAR_PESQUISA'
    if c['estado_revisao_humana'] in ('REJEITADO','APROVADO'):
        screening='DECISAO_EXISTENTE_NAO_ALTERAR'
    # Priority is for operational ordering, not scientific quality.
    score=0
    score+=(11-non_null_note)*3
    score+= 12 if c['ano_validado'] is None else 0
    score+= 10 if not exturls else 0
    score+= 15 if invalid_refs else 0
    score+= 12 if c['categoria'] in ('CONTEXTO_HISTORICO','ESCOPO_DUVIDOSO','POSSIVEL_DUPLICATA') else 0
    score+= 4*min(len(dup),3)
    status='INICIAL' if score>=50 else 'INTERMEDIARIA' if score>=25 else 'FINAL'
    safe_title=c['titulo_proposto'].replace('\r',' ').replace('\n',' ')
    # Deliberately no auto scientific approvals from these flags.
    next_action={'TRIAR_ESCOPO':'Decidir se existe evento tecnológico/científico independente antes de pontuar',
      'TRIAR_DUPLICATA':'Comparar eventos relacionados, decidir manter, separar ou vincular',
      'AUDITAR_PRIORITARIO':'Conferir a data, os documentos e as 11 justificativas; registrar parecer humano',
      'CONFERIR_DATA_FONTES':'Confirmar o intervalo e a sustentação documental antes da aprovação',
      'COMPLETAR_POUCAS_NOTAS':'Pesquisar rubricas SEM_EVIDENCIA com fontes específicas',
      'PESQUISAR_NOTAS':'Pesquisar critérios ainda não sustentados e delimitar o evento',
      'INICIAR_PESQUISA':'Começar pela triagem de escopo; não preencher notas automaticamente',
      'DECISAO_EXISTENTE_NAO_ALTERAR':'Preservar decisão registrada, revisar só com justificativa nova'}[screening]
    rows.append(dict(ID=id_,Titulo=safe_title,Rodada=c['rodada'],Categoria=c['categoria'],Identificacao=c['situacao_identificacao'],Situacao=c['status_avaliacao'],Revisao=c['estado_revisao_humana'],Homologado=c['homologado'],Ano_original=c['ano_original'],Ano_validado=c['ano_validado'],Periodo=c['periodo_historico'],Pagina_livro=c['pagina_livro'],Pagina_pdf=c['pagina_pdf'],Notas_preenchidas=non_null_note,Notas_em_falta=11-non_null_note,Total_rubricas=all_rows,Fontes=len(ss),Fontes_externas=len(external),Fontes_url=len(exturls),Links_de_fontes_inexistentes=len(invalid_refs),Notas_sem_fontes=note_missing_refs,Pendencias=len(p),Duplicatas=len(dup),Ids_duplicatas='; '.join(str(d) for d in dup),Trilha=screening,Esforco_indicativo=status,Proxima_acao=next_action,Primeira_URL_externa=exturls[0] if exturls else '',Resumo_pendencias=' | '.join(str(z) for z in p[:4])))

counts={
'candidatos':len(C),'criterios':len(K),'fontes':len(S),'logs':len(L),'rodadas':len(R),
'codigos_de_rubrica':all_codes,
'estados_revisao':dict(collections.Counter(c['estado_revisao_humana'] for c in C)),
'categorias':dict(collections.Counter(c['categoria'] for c in C)),
'origens_trilhas':dict(collections.Counter(r['Trilha'] for r in rows)),
'por_nota':dict(sorted(collections.Counter(r['Notas_preenchidas'] for r in rows).items())),
'com_ano_validado':sum(c['ano_validado'] is not None for c in C),
'com_fonte_externa':sum(r['Fontes_externas']>0 for r in rows),
'com_URL_externa':sum(r['Fontes_url']>0 for r in rows),
'com_pendencias':sum(r['Pendencias']>0 for r in rows),
'com_duplicatas':sum(r['Duplicatas']>0 for r in rows),
'com_notas_11':sum(r['Notas_preenchidas']==11 for r in rows),
'com_notas_incompletas':sum(r['Notas_preenchidas']<11 for r in rows),
'criterios_pontuados':sum(k['nota'] is not None for k in K),
'criterios_sem_evidencia':sum(k['nota'] is None for k in K),
'integridade_problemas':dict(collections.Counter(i['Tipo'] for i in issues)),
'dump_sha256':SNAPSHOT_SHA256,
}

# Output CSV with protection from formula injection.
def clean_excel(v):
    if v is None:return ''
    s=str(v)
    return "'"+s if s and s[0] in ('=','+','-','@','\t','\r') and not (s.startswith('-') and s[1:].replace('.','').isdigit()) else s

def csvout(name, records, headers=None):
    with (ROOT/name).open('w',encoding='utf-8-sig',newline='') as f:
        wr=csv.DictWriter(f,fieldnames=headers or list(records[0].keys()),delimiter=';')
        wr.writeheader()
        for r in records:wr.writerow({k:clean_excel(v) for k,v in r.items()})

csvout('FILA_COMPLETA_750.csv', rows)
csvout('PROBLEMAS_DE_INTEGRIDADE.csv', issues if issues else [{'ID':'','Tipo':'','Gravidade':'','Detalhe':''}])
csvout('TRIAGEM_DE_ESCOPO.csv', [r for r in rows if r['Trilha'] in ('TRIAR_ESCOPO','TRIAR_DUPLICATA')])
csvout('AUDITORIA_PRIORITARIA.csv',[r for r in rows if r['Trilha'] in ('AUDITAR_PRIORITARIO','CONFERIR_DATA_FONTES','COMPLETAR_POUCAS_NOTAS')])
(ROOT/'METRICAS.json').write_text(json.dumps(counts,indent=2,ensure_ascii=False),encoding='utf-8')
(ROOT/'TRANSFORMACAO_CONFERIDA.json').write_text(json.dumps({k:len(v) for k,v in DATA.items()},indent=2),encoding='utf-8')
if __name__ == "__main__": print(json.dumps({k:v for k,v in counts.items() if k != "categorias"},indent=2,ensure_ascii=False))
