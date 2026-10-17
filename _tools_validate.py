import json, re, sys
from html.parser import HTMLParser

VOID = {"area","base","br","col","embed","hr","img","input","link","meta","param","source","track","wbr"}

class V(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.stack=[]; self.errors=[]; self.h={}; self.imgs=[]; self.links=[]; self.jsonld=[]
        self._cur=None; self.buf=""
    def handle_starttag(self, tag, attrs):
        a=dict(attrs)
        if tag=="script" and a.get("type")=="application/ld+json":
            self._cur="ld"; self.buf=""
        if tag not in VOID:
            self.stack.append((tag,self.getpos()))
        if tag=="img": self.imgs.append(a)
        if tag=="a": self.links.append(a)
        if re.fullmatch(r"h[1-6]", tag): self.h[tag]=self.h.get(tag,0)+1
    def handle_startendtag(self, tag, attrs):
        a=dict(attrs)
        if tag=="img": self.imgs.append(a)
        if tag=="a": self.links.append(a)
    def handle_endtag(self, tag):
        if tag in VOID: return
        if not self.stack: self.errors.append(f"extra </{tag}> at {self.getpos()}"); return
        top,pos=self.stack.pop()
        if top!=tag:
            # allow implied closes for p/li etc handled loosely
            self.errors.append(f"mismatch <{top}> (opened {pos}) closed by </{tag}> at {self.getpos()}")
        if self._cur=="ld" : pass
    def handle_data(self,d):
        if self._cur=="ld": self.buf+=d
    # script content ends with endtag script; capture via stack pop check
def validate(path):
    s=open(path,encoding="utf-8").read()
    v=V(); v.feed(s); v.close()
    leftover=[t for t,_ in v.stack if t not in ("html","body")]
    ld=re.findall(r'<script type="application/ld\+json">(.*?)</script>', s, re.S)
    oks=[]
    for i,blob in enumerate(ld):
        try:
            data=json.loads(blob)
            oks.append((i,True,data))
        except Exception as e:
            oks.append((i,False,str(e)))
    print(f"== {path}")
    print("unclosed:",leftover[:5] or "OK")
    print("errors:",v.errors[:5] or "OK")
    print("H:",v.h)
    bad_alt=[a for a in v.imgs if not a.get("alt")]
    bad_dim=[a for a in v.imgs if not (a.get("width") and a.get("height"))]
    print(f"imgs:{len(v.imgs)} no-alt:{len(bad_alt)} no-w/h:{len(bad_dim)}")
    ext=[a["href"] for a in v.links if a.get("target")=="_blank" and "noopener" not in (a.get("rel") or "")]
    print("blank-no-noopener:",ext)
    anchors=set(re.findall(r'href="#([^"]+)"',s)); ids=set(re.findall(r'id="([^"]+)"',s))
    print("broken anchors:",anchors-ids or "OK")
    for i,ok,data in oks:
        print(f"JSON-LD[{i}]:", "VALID" if ok else f"INVALID: {data}", "| types:", (json.dumps(list({n.get('@type') for n in data.get('@graph',[data])})) if ok else ""))
    canon=re.findall(r'<link rel="canonical" href="([^"]+)"',s); title=re.findall(r'<title>(.*?)</title>',s,re.S)
    print("canonical:",canon,"| title len:",[len(t) for t in title])
validate("index.html")
validate("splav-po-dony-na-ploty/index.html")
