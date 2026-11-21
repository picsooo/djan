# -*- coding: utf-8 -*-
"""Export the mockup content (content.py / content2.py) to cms/app/seed.json."""
import json, os, re, sys
sys.dont_write_bytecode = True
SITE = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "site")
sys.path.insert(0, SITE)
import content as K
import content2 as K2

OUT = sys.argv[1]
LANGS = ("fr", "en")
def img(n): return f"assets/img/{n}.jpg" if n else ""

def links_to_md(s, lang):
    s = (s.replace("{res}", "reservation.html").replace("{trips}", "circuits.html")
          .replace("{unesco}", K.UNESCO_T[lang]).replace("{sebiba}", K.UNESCO_S))
    s = re.sub(r"\{c:([a-z-]+)\}", r"circuit-\1.html", s)
    s = re.sub(r'<a href="([^"]+)"[^>]*>(.*?)</a>', r"[\2](\1)", s)
    assert "<" not in s, s
    return s

docs = []
def add(type_, data, slug="", published=1):
    docs.append(dict(type=type_, slug=slug, position=len([d for d in docs if d["type"] == type_]), published=published, data=data))

# ---------- settings ----------
add("settings", dict(
    site_name="Touring with Mina", logo="", logo_light="",
    wa=K.WA, wa_display="+213 672 48 62 98", phone="+213555304223", phone_display="+213 555 30 42 23",
    email=K.MAIL, notify_email="", address="Djanet, Algérie",
    instagram="https://www.instagram.com/touring_with_mina", facebook="https://www.facebook.com/Touringwithmina",
    domain="https://www.touringwithmina.com", indexable=False, show_illus=True, og_img=img("hero-dunes"), favicon="",
    fr=dict(tagline=K.UI["fr"]["foot"], seo_title="Touring with Mina, voyages privés à Djanet", credit="Site réalisé par Webminds"),
    en=dict(tagline=K.UI["en"]["foot"], seo_title="Touring with Mina, private journeys in Djanet", credit="Website by Webminds"),
), slug="settings")

# ---------- general labels ----------
ui = {}
for L in LANGS:
    u = dict(K.UI[L]); u.update(K2.UI2[L])
    for k in ("switch_min", "switch_ani", "wm", "home", "published"): u.pop(k, None)
    u.update(dict(reviews="Avis", reviews_all="Tous les avis", leave_review="Laisser un avis") if L == "fr" else
             dict(reviews="Reviews", reviews_all="All reviews", leave_review="Leave a review"))
    ui[L] = u
add("page", ui, slug="ui")

# ---------- home ----------
home = dict(
    slides=[img(n) for n in ("hero-dunes", "tadrart-camp", "hero-arch", "ihrir-lake")],
    intro_imgs=[img("tadrart-rocks"), img("tuareg-woman")],
    quote_img=img("mina"), cta_img=img("dunes-car"),
    strip=[img(n) for n in ["hero-arch", "camel", "tents", "essendilene-2", "woman-purple", "rainbow", "fire-man", "sky"]],
    marquee=["Tadrart Rouge", "Ihrir", "Essendilène", "Sefar", "Tin Merzouga", "Idaren", "Sebiba", "Tikoubaouine"],
    unesco_url=K.UNESCO_T["fr"],
)
for L in LANGS:
    H = dict(K.HOME[L]); H2 = K2.HOME2[L]
    H.pop("scroll", None)
    H["facts"] = [dict(n=n, t=t) for n, t in H["facts"]]
    H["incl"] = [dict(t=t, p=p, img=img(i)) for t, p, i in H["incl"]]
    H.update(H2)
    H.update(dict(rev_h="Ils sont partis avec Mina", rev_p="Les mots de nos voyageurs, à leur retour du désert.") if L == "fr" else
             dict(rev_h="They travelled with Mina", rev_p="Words from our travellers, back from the desert."))
    home[L] = H
add("page", home, slug="home")

# ---------- day in the desert ----------
add("page", {L: dict(h=K2.DAY[L]["h"], p=K2.DAY[L]["p"], steps=[dict(k=a, t=b, p=c, img=img(d)) for a, b, c, d in K2.DAY[L]["steps"]]) for L in LANGS}, slug="day")

# ---------- about ----------
about = dict(hero_img=img("group-dune"))
for L in LANGS:
    A = K.ABOUT[L]; P = K2.PROCESS[L]
    about[L] = dict(h=A["h"], lead=A["lead"], blocks=[dict(t=t, p=p, img=img(i)) for t, p, i in A["blocks"]],
                    values_h=A["values_h"], langs=A["langs"], values=[dict(t=t, p=p) for t, p in A["values"]],
                    proc_h=P["h"], proc_p=P["p"], proc=[dict(t=t, p=p) for t, p in P["steps"]])
add("page", about, slug="about")

add("page", dict(hero_img=img("tadrart-camp"), **{L: dict(K.TRIPS_UI[L], included=list(K.INCLUDED[L]), not_included=list(K.NOT_INCLUDED[L])) for L in LANGS}), slug="trips")
add("page", dict(hero_img=img("peaks"), **{L: dict(K2.DEST_UI[L]) for L in LANGS}), slug="destinations")
add("page", {L: dict(h=K.GAL_UI[L]["h"], p=K.GAL_UI[L]["p"], filters=[dict(k=k, t=t) for k, t in K.GAL_UI[L]["f"]]) for L in LANGS}, slug="gallery")
add("page", {L: dict(K.BLOG_UI[L]) for L in LANGS}, slug="blog")
add("page", {L: dict(h=K2.FAQ[L]["h"], p=K2.FAQ[L]["p"], more=K2.FAQ[L]["more"], more_p=K2.FAQ[L]["more_p"]) for L in LANGS}, slug="faq")
add("page", {L: dict(K2.CONTACT[L], saved=("Message enregistré. Mina vous répond très vite." if L == "fr" else "Message saved. Mina will reply very soon.")) for L in LANGS}, slug="contact")
add("page", dict(**{L: (dict(h="Avis de nos voyageurs", p="Ce que racontent ceux qui sont partis avec nous. Vous avez voyagé avec Mina ? Laissez votre avis.",
                             form_h="Laisser un avis", name="Votre prénom", origin="Votre pays", trip="Votre circuit", rating="Votre note", text="Votre avis",
                             send="Envoyer mon avis", thanks="Merci ! Votre avis sera publié après relecture.", error="Merci de remplir votre prénom, votre note et votre avis.", empty="Les premiers avis arrivent bientôt.")
                        if L == "fr" else
                        dict(h="Reviews from our travellers", p="What those who travelled with us say. Did you travel with Mina? Leave a review.",
                             form_h="Leave a review", name="Your first name", origin="Your country", trip="Your tour", rating="Your rating", text="Your review",
                             send="Send my review", thanks="Thank you! Your review will be published after moderation.", error="Please fill in your first name, rating and review.", empty="The first reviews are coming soon.")) for L in LANGS}), slug="reviews")

infos = dict(visa_src=K2.VISA_SRC)
for L in LANGS:
    I = K2.INFO[L]
    infos[L] = dict(h=I["h"], p=I["p"], note=I["note"],
                    secs=[dict(id=i, t=t, ps=list(ps), li=list(li or [])) for i, t, ps, li in I["secs"]],
                    seasons=[dict(t=t, p=p) for t, p in I["seasons"]])
add("page", infos, slug="infos")

book = {}
for L in LANGS:
    B = dict(K.BOOK[L]); B["f"] = dict(B["f"])
    B.update(dict(saved="Votre demande a bien été enregistrée. Mina vous répond sous 24 heures.") if L == "fr" else
             dict(saved="Your request has been saved. Mina will reply within 24 hours."))
    book[L] = B
add("page", book, slug="booking")

# ---------- circuits ----------
for c in K.CIRCUITS:
    d = dict(img=img(c["img"]), imgs=[img(n) for n in c["imgs"]], d=c["d"], n=c["n"], price="")
    for L in LANGS:
        d[L] = dict(t=c["t"][L], s=c["s"][L], level=c["level"][L], season=c["season"][L], group=c["group"][L],
                    tags=list(c["tags"][L]), p=[dict(t=t, p=p) for t, p in c["p"][L]])
    add("circuit", d, slug=c["id"])

for dd in K2.DESTS:
    d = dict(img=img(dd["img"]), img2=img(dd["img2"]), word=dd["word"], circuit=dd["trip"])
    for L in LANGS:
        t = dd[L]; d[L] = dict(t=t["t"], s=t["s"], p=list(t["p"]), see=list(t["see"]), acc=t["acc"])
    add("destination", d, slug=dd["id"])

for p in K.POSTS:
    d = dict(date=p["date"], img=img(p["img"]))
    for L in LANGS:
        body = "\n\n".join(("## " + t) if k == "h2" else links_to_md(t, L) for k, t in p["b"][L])
        d[L] = dict(t=p["t"][L], d=p["d"][L], body=body)
    add("post", d, slug=p["slug"])

home_q = {(0, 0), (0, 2), (1, 1), (2, 1)}
for gi in range(len(K2.FAQ["fr"]["groups"])):
    for qi in range(len(K2.FAQ["fr"]["groups"][gi][1])):
        d = dict(home=(gi, qi) in home_q)
        for L in LANGS:
            g, qs = K2.FAQ[L]["groups"][gi]; q, a = qs[qi]
            d[L] = dict(g=g, q=q, a=links_to_md(a, L))
        add("faq", d)

for n, c in K.GALLERY:
    add("photo", dict(img=img(n), cat=c))

# Sample reviews: clearly labelled as examples (sample=True shows the "illustrative" badge on the site).
samples = [
    ("Avis d'exemple", "France", "tadrart-rouge", 5, "fr", "Texte d'exemple : remplacez-le par le vrai avis d'un voyageur depuis l'administration, rubrique Avis."),
    ("Sample review", "United Kingdom", "trek-tassili", 5, "en", "Sample text: replace it with a real traveller's review from the admin, in the Reviews section."),
    ("Avis d'exemple", "Algérie", "djanet-en-famille", 5, "fr", "Texte d'exemple : les avis envoyés depuis le site arrivent dans l'administration et ne sont publiés qu'après votre validation."),
]
for name, origin, trip, r, lang, text in samples:
    add("review", dict(name=name, origin=origin, circuit=trip, rating=r, date="2026-11-01", lang=lang, text=text, sample=True))

json.dump(docs, open(OUT, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
print(len(docs), "docs")
