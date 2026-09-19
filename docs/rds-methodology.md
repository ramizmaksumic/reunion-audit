# Reunion Digital Standard — Master Spec

## 1. Svrha dokumenta, status i evolucija metodologije

Ovaj dokument zamjenjuje sve prethodne verzije (Word v1.0, v1.1, i pojedinačne Excel workbookove) i predstavlja jedinstven izvor istine za Reunion Digital Standard (RDS) metodologiju, na osnovu koje se gradi TALL stack aplikacija. Status: metodologija je u fazi interne revizije — spremna za arhitekturu aplikacije uz nekoliko preporuka datih u sekciji 10 (Ponderi i metodološki nalazi).

RDS mjeri digitalnu zrelost kompanije, ne poslovnu zrelost. Pitanje pripada standardu samo ako ga digitalna agencija može objektivno provjeriti i, kad postoji nedostatak, predložiti ili implementirati konkretno digitalno rješenje.

Evolucija metodologije kroz radne verzije:

- **v1.0/v1.1 (Word)** — 7 glavnih oblasti sa ponderisanim podoblastima i skalom nivoa 0–4 po podoblasti. Konceptualno čisto, ali teško za kalibraciju između auditora.
- **Excel workbook model** — svaka oblast/podoblast postaje samostalan workbook sa ravnom listom kriterija; svaki kriterij nosi jednaku vrijednost (100 ÷ broj kriterija), sa unaprijed definisanim opcijama odgovora direktno mapiranim na bodove. Prioritet (Kritičan/Važan/Preporučen) odvojen je od bodovanja.
- **v2.0 konsolidacija** — "Analitika i odlučivanje" ukinuta kao zasebna oblast (analitika se sada procjenjuje unutar weba, marketinga i digitalne efikasnosti); Mystery Shopping potvrđen kao dokazna metoda, ne oblast; "Digitalna efikasnost" redizajnirana od pristupa "koji alat koristite" ka procesnom pristupu; "Marketing i rast" podijeljen na šest samostalnih podoblasti/workbookova.

## 2. Arhitektura standarda — pregled svih oblasti i workbookova

Ukupno sistem trenutno broji **~420 kriterija kroz 14 bodovanih workbookova**, grupisanih u 4 potvrđene glavne oblasti plus jednu otvorenu odluku, uz dva ne-bodovana pomoćna dokumenta (Profil kompanije, Mystery Shopping protokol).

| Glavna oblast | Status | Workbookovi | Kriterija |
| --- | --- | --- | --- |
| 1. Digitalna prisutnost | Potvrđena (grupisanje 6 workbookova čeka odluku o Vidljivosti) | Web, Google Business, SEO Prisutnost, Digitalna Reputacija, Brend i Identitet, Povjerenje i Kredibilitet | 192 |
| — Vidljivost i pronalaženje | Otvorena odluka | (kriteriji se vjerovatno raspoređuju u Digitalnu prisutnost, vidi sekciju 5) | — |
| 2. Korisničko iskustvo i konverzija | Potvrđena | 1 workbook (6 grupa I–VI) | 24 |
| 3. Digitalna efikasnost i automatizacija | Potvrđena, redizajnirana u v2.0 | 1 workbook (6 procesnih grupa) | 24 |
| 4. Marketing i rast | Potvrđena, restrukturisana u 6 podoblasti | Strategija, Akvizicija, Tržišna komunikacija, Kampanje, Zadržavanje, Analitika | 180 |
| **Ukupno** | | **14 workbookova** | **~420** |

Pomoćni dokumenti bez bodovanja:

- **Profil kompanije** — kontekstualni upitnik proveden prije audita; određuje koji su digitalni kanali/kriteriji uopšte relevantni za konkretan poslovni model (vidi sekciju 9).
- **Mystery Shopping protokol** — standardizovan scenario testiranja stvarnog korisničkog iskustva; služi kao dokaz za određene kriterije, prvenčstveno u Korisničkom iskustvu (vidi sekciju 9).

## 3. Osnovna metodologija bodovanja

Svaki workbook vrijedi tačno 100 bodova, bez obzira na broj kriterija. Vrijednost jednog kriterija = 100 ÷ broj kriterija u workbooku (npr. 24 kriterija = 4,17 boda; 30 kriterija = 3,33 boda; 66 kriterija = 1,52 boda). Auditor nikad ne upisuje bodove ručno — samo bira jednu od unaprijed ponuđenih opcija odgovora, a sistem automatski izračunava bodove.

| Tip kriterija | Opis | Primjer |
| --- | --- | --- |
| Binarni | Da / Ne | "HTTPS aktivan" — Da = puni bodovi, Ne = 0 |
| Djelimičan | Unaprijed definisani nivoi | "Da / Djelimično / Ne" sa opadajućim bodovima |
| Metrički (prag) | Unaprijed definisani tehnički pragovi | Lighthouse 90–100 / 70–89 / 50–69 / <50 |
| Audit opažanje | Standardizovana stručna procjena | "Odlično / Dobro / Osnovno / Loše" |

Prioritet kriterija (Kritičan / Važan / Preporučen) je potpuno odvojen od bodovanja — ne mijenja broj bodova, već označava ozbiljnost i hitnost nalaza za akcioni plan.

**N/A pravilo:** stavka koja stvarno nije primjenjiva na poslovni model (ne stavka koju firma jednostavno nema) označava se N/A i ne smije automatski rušiti rezultat. Preostali primjenjivi kriteriji se proporcionalno normalizuju na 100. Relevantnost određuje Profil kompanije (sekcija 9).

## 4. Oblast: Digitalna prisutnost

Temelj standarda — procjenjuje da li kompanija ima profesionalnu, tehnički ispravnu, konzistentnu i vjerodostojnu digitalnu osnovu. Čini je šest workbookova; konačno grupisanje/ponderi između njih čekaju odluku o Vidljivosti (sekcija 5).

| Workbook | Kriterija | Šta mjeri | Glavni alati/dokazi |
| --- | --- | --- | --- |
| Web stranica | 66 | Dostupnost, performanse (Lighthouse/PSI), SEO osnove, tehnička ispravnost, indeksiranje, funkcionalnost, kvalitet prezentacije | Lighthouse, PageSpeed Insights, Screaming Frog, DevTools, Rich Results Test, ručni test |
| Google Business profil | 30 | Tačnost podataka, verifikacija, optimizacija profila, fotografije, recenzije, aktivnost, funkcionalnost | Google Business profil, ručni test |
| SEO Prisutnost | 24 | Indeksiranost, organska vidljivost, ključne riječi, autoritet domene, lokalni SEO, praćenje | Google Search Console, GA4, Ahrefs/Semrush |
| Digitalna Reputacija | 24 | Online recenzije, upravljanje recenzijama, spominjanje brenda, povjerenje, transparentnost, krizna komunikacija | Google/Facebook/Booking, Google Search, ručni audit |
| Brend i Identitet | 24 | Vizuelni identitet, konzistentnost naziva/kontakta, komunikacija, digitalni kanali, profesionalni identitet | Ručni audit svih kanala |
| Povjerenje i Kredibilitet | 24 | Transparentnost poslovanja, reference, profesionalni kredibilitet, sigurnost/usklađenost, stručnost, korisničko povjerenje | Ručni audit |

Web workbook je najdetaljniji tehnički audit u cijelom standardu. Google Business, reputacija, brend i povjerenje više se oslanjaju na ručni audit i standardizovane opcije odgovora nego na automatske alate.

## 5. Vidljivost i pronalaženje — preporučena odluka

**Preporuka: integrisati u Digitalnu prisutnost, ne zadržavati kao zasebnu glavnu oblast.**

Izvorna svrha — koliko je kompaniju lako pronaći kad korisnik aktivno traži njene proizvode/usluge — već je pokrivena kroz SEO Prisutnost i Google Business workbookove unutar Digitalne prisutnosti. Ta dva workbooka mjere upravo pronalazivost kroz pretragu, mape i lokalne signale.

Razlog za integraciju umjesto dijeljenja kriterija između Digitalne prisutnosti i Marketinga: Digitalna prisutnost pokriva imovinu i kanale koje kompanija posjeduje i kontroliše (web, GBP, SEO, brend, reputacija) — vidljivost je direktan ishod kvaliteta te imovine. Marketing i rast pokriva aktivno stvaranje potražnje (akvizicija, kampanje, sadržaj) — drugačiju vrstu napora. Dijeljenje kriterija između dvije oblasti rizikuje dupliranje (SEO tehnika već je u Web workbooku, SEO sadržaj/rangiranje bi bio negdje između).

Ako se ipak testiranjem na stvarnim kompanijama pokaže da SEO i GBP workbookovi ne pokrivaju sve što je izvorno bilo namijenjeno Vidljivosti (npr. prisustvo na industrijskim platformama poput Booking/Tripadvisor/marketplace-a za specifične djelatnosti), preporuka je dodati te kriterije kao novu grupu unutar SEO Prisutnosti ili kao poseban mali workbook unutar Digitalne prisutnosti — ne kao sedmu glavnu oblast.

## 6. Oblast: Korisničko iskustvo i konverzija

Odgovara na pitanje: koliko je digitalni nastup kompanije optimizovan da posjetioca pretvori u kupca ili potencijalnog klijenta? Procjena nije ograničena na web stranicu — prati kompletan korisnički put kroz sve relevantne digitalne kanale (web, email, kontakt forme, društvene mreže, Google Business, telefon, Viber, WhatsApp).

24 kriterija, 100 bodova, 4,17 boda po kriteriju, podijeljeno u šest grupa:

| Grupa | Fokus |
| --- | --- |
| I. Prvi kontakt | Koliko je lako pronaći način za kontakt i napraviti prvi korak |
| II. Komunikacija | Da li kompanija odgovara, koliko brzo, profesionalno i korisno |
| III. Proces konverzije | Jednostavnost kupovine, rezervacije, upita ili druge glavne radnje |
| IV. Korisnička podrška | Dostupnost pomoći, informacija, potvrda i podrške |
| V. Povjerenje tokom procesa | Sigurnost, transparentnost, jasna očekivanja |
| VI. Ukupno korisničko iskustvo | Logičan i usklađen put kroz kanale, bez nepotrebnog trenja |

**Mystery Shopping** koristi se ovdje kao dokazna metoda za kriterije koji se ne mogu vjerodostojno ocijeniti samo statičnim pregledom (npr. vrijeme odgovora, kvalitet komunikacije) — ne dobija vlastiti score. Zbog toga ova oblast, za razliku od ostalih, po prirodi zahtijeva auditora i teško je u potpunosti samoposlužna (vidi sekciju 11 build plana).

## 7. Oblast: Digitalna efikasnost i automatizacija (v2.0)

Značajno redizajnirana u odnosu na raniju verziju. Više ne pita "da li kompanija koristi CRM/ERP/AI" — sama prisutnost tehnologije ne dokazuje efikasnost. Audit prvo posmatra stvarni poslovni proces, tek zatim procjenjuje da li ga digitalni alati podržavaju.

24 kriterija, 100 bodova, 4,17 boda po kriteriju, šest procesnih grupa:

| Grupa | Šta se procjenjuje |
| --- | --- |
| I. Upravljanje upitima kupaca | Kako se upiti zaprimaju, evidentiraju, prate i obrađuju |
| II. Prodaja i ponude | Kako se pripremaju ponude, prati prodajni tok |
| III. Operativni procesi | Digitalna organizacija dokumentacije, jasnoća toka svakodnevnih procesa |
| IV. Interna saradnja | Zajednički alati, odgovornosti, statusi zadataka, centralizacija dokumenata |
| V. Donošenje odluka | Dostupnost tačnih digitalnih podataka i izvještaja |
| VI. Kontinuirano unapređenje | Analiza procesa, uvođenje automatizacija/AI samo kad rješavaju konkretan problem |

Metodološko pravilo: tehnologija se ne boduje zbog same tehnologije. CRM, integracija, automatizacija ili AI imaju vrijednost samo ako poboljšavaju konkretan proces, smanjuju ručni rad, greške ili vrijeme. Ovaj pristup drži metodologiju dugoročno primjenjivom bez obzira na promjene tehnologije.

## 8. Oblast: Marketing i rast

Najveća oblast po broju kriterija (180) i jedina restrukturisana u šest samostalnih podoblasti/workbookova, svaki 100 bodova. Ukupni redoslijed odgovara logičnom toku: prvo strategija, zatim akvizicija, komunikacija, kampanje, zadržavanje, na kraju mjerenje.

| Podoblast | Kriterija | Ključno pitanje |
| --- | --- | --- |
| Strategija i planiranje marketinga | 24 | Da li se marketing vodi planski ili stihijski? |
| Privlačenje novih kupaca (Akvizicija) v2.0 | 60 | Koje kanale kompanija koristi, koliko ih razumije, mjeri i koliko su relevantni? |
| Tržišna komunikacija | 24 | Koliko kvalitetno kompanija komunicira svoju vrijednost prema tržištu? |
| Kampanje i oglašavanje | 24 | Da li kompanija planski upravlja kampanjama, budžetom, mjerenjem i optimizacijom? |
| Zadržavanje i razvoj kupaca | 24 | Koliko uspješno kompanija zadržava postojeće kupce i razvija dugoročne odnose? |
| Analitika i optimizacija marketinga | 24 | Da li se marketinški rezultati mjere i koriste za unapređenje? |

Pet od šest podoblasti (sve osim Akvizicije) prate identičan obrazac: 24 kriterija u šest grupa (A–F) po 4 kriterija, sva binarna/djelimična (Da=4,17 / Djelimično=2,08 / Ne=0), dokazana kroz intervju + analitiku + uvid u materijale.

**Akvizicija v2.0 je strukturno drugačija** — umjesto opštih pitanja, mapira 10 konkretnih kanala (Google Business, SEO, Google Ads, Meta Ads, LinkedIn, Email marketing, YouTube, TikTok, Partnerstva i preporuke, Ostali kanali), a za svaki kanal postavlja identičnih 6 pitanja: koristi li se kanal, održava li se aktivno, prate li se rezultati, zna li kompanija obim upita/kupaca odatle, smatra li ga kompanija važnim, i smatra li ga auditor relevantnim za taj poslovni model. Zaključci (koji kanali nedostaju, gdje kompanija pogrešno procjenjuje potencijal) izvode se analizom odgovora, ne postavljaju se direktno kao pitanja. Ova struktura ima važan metodološki nedostatak obrađen u sekciji 10.

## 9. Mystery Shopping i Profil kompanije

**Mystery Shopping protokol** — pomoćna metodologija, ne oblast. Ne dobija zaseban score i ne ulazi u ukupni Reunion Digital Score. Auditor se predstavlja kao stvarni potencijalni korisnik, ne otkriva da provodi audit, koristi stvarne digitalne kanale kompanije i dokumentuje svaki kontakt (screenshotovi, zapisnici). Standardni tok: pronaći kanal → poslati upit → analizirati odgovor → postaviti dodatno pitanje → pokušati glavnu radnju → provjeriti potvrde → ocijeniti kompletan put. Za različite djelatnosti (hotel, webshop, restoran, ordinacija, agencija) mogu se razviti posebni scenariji.

**Profil kompanije** — takođe nije bodovna oblast. Prikuplja se prije audita i definiše: djelatnost, poslovni model (B2B/B2C, fizička lokacija, online prodaja, rezervacije, broj poslovnica), i za svaki digitalni kanal označava da li je Kritičan / Preporučen / Nije relevantan za tu konkretnu kompaniju.

Ova dva dokumenta rade zajedno: Profil kompanije određuje koji su kriteriji/kanali primjenjivi (N/A logika iz sekcije 3), a Mystery Shopping daje dokaz za kriterije koje nije moguće ocijeniti samo pregledom. Za aplikaciju ovo znači da Profil kompanije treba biti prvi korak svakog audita — njegovi odgovori programski postavljaju koji kriteriji iz svih 14 workbookova ulaze u obračun, a koji se automatski označavaju N/A.

## 10. Ponderi i metodološki nalazi

### Ponderi (privremeno rješenje)

Ni ponderi glavnih oblasti u ukupnom Reunion Digital Score-u, ni ponderi između workbookova unutar Digitalne prisutnosti i Marketinga, još nisu zaključeni. Prema principu iz izvornog dokumenta ("ne optimizovati metodologiju beskonačno u teoriji"), preporuka je: **krenuti sa ravnomjernim ponderisanjem svugdje** (svaka glavna oblast i svaki workbook unutar nje jednako teži) i čuvati ponder kao podatak u bazi, ne hardkodovan u kodu. Nakon prvih par stvarnih audita, ponderi se mogu kalibrisati na osnovu toga koje oblasti auditori i klijenti stvarno smatraju najuticajnijima na prihod/rizik, bez ijedne izmjene koda.

### Nalaz: N/A-gate problem u Akviziciji

Workbook "Privlačenje novih kupaca v2.0" tretira svih 60 kriterija (10 kanala × 6 pitanja) kao ravnopravne, po 1,67 boda. Jedno od tih 6 pitanja po kanalu je "auditor smatra da je ovaj kanal relevantan za ovu kompaniju". Ako se to pitanje boduje kao običan kriterij, kompanija koja ispravno koristi samo 3 relevantna kanala automatski gubi bodove za preostalih 7 kanala × 6 pitanja — što direktno krši opšte N/A pravilo iz sekcije 3 (N/A ne smije rušiti rezultat).

**Preporučena ispravka:** "relevantnost kanala" pitanje treba funkcionisati kao N/A-gate, ne kao bodovani kriterij. Kad auditor označi kanal kao nerelevantan, preostalih 5 pitanja za taj kanal automatski postaju N/A, a bodovi workbooka se normalizuju na broj primjenjivih kanala × 5 pitanja. Ovo je isti mehanizam koji već postoji u opštoj metodologiji — samo treba eksplicitno implementirati na nivou pojedinačnog workbooka, ne samo na nivou cijelog audita.

## 11. Model provođenja audita — dva sloja

~420 kriterija je previše za klijenta da samostalno prođe u jednoj sesiji "Započni procjenu" bez napuštanja aplikacije na pola puta, i mnogi kriteriji (Mystery Shopping, ručni audit vizuelnog identiteta, intervjui o internim procesima) fizički zahtijevaju auditora. Predložen je dvoslojni model:

| Sloj | Ko provodi | Opseg | Svrha |
| --- | --- | --- | --- |
| Brzi scan | Klijent, samoposluga | ~60–80 objektivno provjerljivih/kritičnih kriterija | Brz orijentacioni rezultat, generisanje leada |
| Puni Reunion audit | Auditor, uz mystery shopping | Svih ~420 kriterija kroz 14 workbookova | Kompletna dijagnoza, prodajni/konsultantski proizvod |

Za implementaciju to znači da svaki kriterij u bazi ima oznaku moda (`self_service_eligible: da/ne`) pored postojećih atributa (tip, opcije, bodovi, prioritet, alat). MVP počinje isključivo sa punim auditor-vođenim modom — samoposlužni sloj je backlog stavka (vidi `docs/mvp-build-plan.md`).

## 12. Očekivani izlazi i dashboard koncept

Očekivani izlazi za klijenta: ukupni Reunion Digital Score (nakon zaključavanja pondera) i score svake glavne oblasti/workbooka; Lighthouse-style kružni indikatori, vizuelni pregled snaga i slabosti; top nalazi grupisani po prioritetu (Kritičan/Važan/Preporučen) i akcioni plan po vremenskom horizontu (7–15 dana, 31–90 dana, 3–12 mjeseci); dokazi i napomene auditora, mogućnost poređenja rezultata kroz vrijeme.

Otvoreno prije verzije 1.0 za tržište: odluka o Vidljivosti (preporuka u sekciji 5) i konačno grupisanje SEO/GBP/reputacije/brenda/povjerenja; zaključivanje pondera glavnih oblasti i workbookova unutar njih; implementacija N/A-gate ispravke za Akviziciju (sekcija 10); testiranje metodologije na stvarnim kompanijama prije daljeg teorijskog proširivanja.
