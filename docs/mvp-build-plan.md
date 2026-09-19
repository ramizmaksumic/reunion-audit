# MVP Build Plan i Promptovi za Claude Code

## 1. Poslovni kontekst i cilj MVP-a

Cilj nije aplikacija sama po sebi — cilj je da Ramizova agencija stiže do 10.000 KM/mjesečno stabilnog prihoda brže, prodajom **jednog integrisanog paketa digitalnog marketinga** (npr. 5 klijenata × ~2.000 KM/mjesec) umjesto pojedinačnih usluga (web, Google Ads, društvene mreže...) prodavanih zasebno.

Reunion Digital Standard je alat koji to omogućava: kompletan audit kompanije postaje **prodajni dokaz** ("evo tačno gdje gubite novac i zašto") koji opravdava paket cijenu, umjesto da se svaka usluga prodaje pojedinačno i teško brani cijenu.

Zašto aplikacija, ne Excel: klijent (ili vlasnik firme na sastanku) treba dobiti utisak ozbiljnog, profesionalnog proizvoda — dashboard sa scoreom, graficima i akcionim planom ostavlja mnogo jači utisak nego 20 papira i Excel tabele, čak i ako je matematika iza toga identična.

## 2. Preporuka o opsegu MVP-a

**Ne redukovati broj pitanja. Redukovati funkcionalni obim.**

Cijena učitavanja 24 kriterija ili 420 kriterija u bazu je identična — to je samo seed podataka (već pripremljen, vidi `database/seeders/data/rds_methodology_seed.json`). Nema razloga praviti "skraćenu" verziju metodologije koju bi trebalo posebno održavati kao drugi izvor istine.

Ono što stvarno treba redukovati za v1:

| Odlučeno ZA MVP | Odloženo za kasnije |
| --- | --- |
| Auditor sam unosi odgovore (interni alat) | Klijent self-service "brzi scan" |
| Jedan tenant (Ramizova agencija) | Multi-tenant SaaS |
| Puna metodologija (420 kriterija) kao podaci | — (ne skraćivati) |
| Dashboard + osnovni PDF izvoz | Poređenje kroz vrijeme / trend grafici |
| Ravnomjerni ponderi (editabilni u adminu) | Kalibrisani ponderi na osnovu stvarnih audita |
| Filament admin za CRUD potrebe (samo interni dio) | Prilagođen branded UI za auditore |

**Redoslijed gradnje:** prvo izgraditi kompletan tehnički tok (šema → scoring → unos → dashboard) na JEDNOJ oblasti (Web, jer je najveća i najimpresivnija sa Lighthouse integracijom), potvrditi da sve radi tačno, zatim ubaciti preostalih 13 workbookova kao čistu seed operaciju — arhitektura se time ne mijenja, samo raste količina podataka.

## 3. Pregled faza

Svaka faza ispod (4–10) ima gotov prompt koji možeš direktno kopirati u Claude Code. Radi ih redom — svaka sljedeća pretpostavlja da je prethodna završena i prošla kroz tvoju provjeru.

| Faza | Cilj | Rezultat |
| --- | --- | --- |
| 0. Provjera projekta | Postojeći Laravel 13 + MySQL projekat, TALL stack, auth | Aplikacija se pokreće, migracije prolaze |
| 1. Data model i scoring | Šema baze + servis za bodovanje | Testiran scoring engine (još bez UI-ja) |
| 2. Seed metodologije | Učitavanje svih 420 kriterija | Baza puna stvarnih RDS podataka |
| 3. Admin (Filament) | CRUD za kompanije, oblasti, kriterije | Možeš sam mijenjati pitanja bez developera |
| 4. Unos audita | Wizard za auditora kroz workbookove | Možeš provesti kompletan audit u aplikaciji |
| 5. Dashboard | Prikaz rezultata po uzoru na mockup | Vizuelni izvještaj spreman za prodajni sastanak |
| 6. Akcioni plan i PDF | Automatske preporuke + izvoz | Dokument koji možeš poslati klijentu |

Nakon faze 6 imaš radeći MVP dovoljan za evaluaciju na stvarnim klijentima. Backlog za kasnije je u sekciji 11.

## 4. Faza 0 — Provjera postojećeg projekta (Laravel 13 + MySQL)

> Ovo je postojeći Laravel 13 projekat sa MySQL bazom već povezanom kroz .env (kredencijale sam već upisao). Ne kreiraj novi projekat i ne mijenjaj konekciju na bazu.
>
> Provjeri i dovrši TALL stack setup: potvrdi da su Livewire 3 i Alpine.js instalirani (instaliraj ih ako nedostaju), potvrdi da je Tailwind CSS konfigurisan i da se kompajlira (`npm run build` / Vite). Pokreni `php artisan migrate` da potvrdiš da se aplikacija uspješno povezuje na MySQL bazu.
>
> Postavi jednostavnu autentifikaciju za internog admin/auditor korisnika (Laravel Breeze, ili Filamentov ugrađeni login ako izaberemo Filament — vidi napomenu u Fazi 3). Ne treba javna registracija niti multi-tenant logika u ovoj fazi.
>
> Napravi prvi git commit sa ovim početnim stanjem.

Provjeri prije prelaska na Fazu 1: migracije prolaze na MySQL bazi, Tailwind/Livewire/Alpine rade (proba jedne jednostavne Livewire komponente na test ruti), login radi, git commit postoji.

## 5. Faza 1 — Data model i scoring engine

> Napravi migracije i Eloquent modele za Reunion Digital Standard po ovoj šemi:
>
> - `companies`: name, industry, business_model_notes (text), b2b_or_b2c, market_scope, has_physical_location (bool), sells_online (bool), provides_online_services (bool), works_by_appointment (bool), has_multiple_locations (bool)
> - `company_channel_relevance`: company_id, channel_key (string), relevance (enum: critical/recommended/not_relevant)
> - `areas`: key, name, sort_order
> - `workbooks`: area_id (FK), key, name, sort_order, weight (decimal, default jednako podijeljeno unutar oblasti)
> - `criteria`: workbook_id (FK), external_id (npr. "WEB-001"), group_label, text, answer_type (enum: binary/threshold/graded/audit_opinion), priority (enum: kritican/vazan/preporucen), evidence_source, self_service_eligible (bool), is_relevance_gate (bool), sort_order
> - `criterion_options`: criterion_id (FK), label, points (decimal), sort_order
> - `assessments`: company_id (FK), methodology_version (string), mode (enum: quick_scan/full_audit), status (enum: draft/in_progress/completed), started_at, completed_at, created_by (FK user)
> - `assessment_answers`: assessment_id (FK), criterion_id (FK), selected_option_id (FK nullable), is_na (bool), na_reason (nullable string), evidence_path (nullable string), notes (nullable text)
>
> Zatim napravi `app/Services/ScoringService.php` sa metodama:
> - `workbookScore(Assessment $assessment, Workbook $workbook): float` — zbir ostvarenih bodova primjenjivih (ne-N/A) kriterija, normalizovan na 100 u odnosu na zbir maksimalnih bodova primjenjivih kriterija.
> - `areaScore(Assessment $assessment, Area $area): float` — ponderisani prosjek workbookScore() njegovih workbookova (koristi kolonu `weight`).
> - `overallScore(Assessment $assessment): float` — ponderisani prosjek areaScore() svih oblasti.
> - Implementiraj i "relevance gate" logiku: kad je odgovor na kriterij sa `is_relevance_gate = true` negativan (najniži bodovi), svi ostali kriteriji iz iste `group_label` unutar tog workbooka automatski se tretiraju kao N/A pri računanju (ovo je ključno za Akvizicija workbook, gdje svaki od 10 kanala ima svoj gate-kriterij).
>
> Napiši Pest ili PHPUnit testove za ScoringService koji pokrivaju: potpuno popunjen workbook, workbook sa nekoliko N/A kriterija (provjeri normalizaciju), i relevance-gate scenario (irelevantan kanal ne smije srušiti rezultat).

## 6. Faza 2 — Seed kompletne metodologije

U repo dodaj `database/seeders/data/rds_methodology_seed.json` — sve 4 oblasti, 14 workbookova i 420 kriterija sa opcijama odgovora, bodovima, prioritetima i oznakama (self_service_eligible, is_relevance_gate), izvučeno direktno iz Excel workbookova. Sadrži i Profil kompanije pitanja i Mystery Shopping korake (oba ne-bodovana).

> U repo sam dodao `database/seeders/data/rds_methodology_seed.json`. Struktura: `{"areas": {...}, "workbooks": [{area_key, area_name, workbook_key, workbook_name, id_prefix, criteria_count, points_per_criterion, criteria: [{id, group, text, options: [{label, points}], priority, evidence_source, answer_type, self_service_eligible, is_relevance_gate}]}], "profil_kompanije": [{section, question}], "mystery_shopping_protocol": [{step, activity, channel, measures, evidence}]}`.
>
> Napiši `RdsMethodologySeeder` koji čita ovaj JSON i puni `areas`, `workbooks`, `criteria` i `criterion_options` tabele (koristi `updateOrCreate` po `key`/`external_id` da seeder bude idempotentan — može se ponovo pokrenuti kad se metodologija promijeni). Weight kolona za workbookove: podijeli ravnomjerno unutar svake oblasti (npr. Digitalna prisutnost ima 6 workbookova → svaki weight = 1/6), i ravnomjerno između 4 glavne oblasti.
>
> Registruj seeder u `DatabaseSeeder` i pokreni ga. Nakon toga ispiši u konzoli broj kriterija po workbooku i potvrdi da je ukupno 420.

## 7. Faza 3 — Filament admin

**Preporuka: Filament da, ali samo za interni admin CRUD (kompanije, oblasti, workbookovi, kriteriji) — ne za tok unosa audita ni za klijentski dashboard.** Filament je najbrži put do funkcionalnog CRUD-a nad metodologijom, i tačno je za taj posao napravljen. Wizard za unos audita (Faza 4) i dashboard rezultata (Faza 5) trebaju custom Livewire/Blade jer zahtijevaju specifičan tok kroz workbookove i vizuelni identitet (kao na mockupu) koji Filament ne nudi bez značajnog prilagođavanja. Nije potrebno birati jedno ili drugo — Filament i custom Livewire rade zajedno u istoj Laravel aplikaciji, svako na svom dijelu.

> Napravi Filament Resource-e za:
> - `Company` — forma sa svim poljima iz Profil kompanije (osnovni podaci, poslovni model), plus repeater/relation manager za `company_channel_relevance` (lista kanala sa dropdown: kritičan/preporučen/nije relevantan).
> - `Area`, `Workbook` — jednostavan CRUD sa poljima key/name/sort_order/weight, ugniježden tako da se iz Area vidi lista njenih Workbookova.
> - `Criterion` — CRUD sa relation manager-om za `CriterionOption` (repeater: label + points), plus polja group_label, text, answer_type, priority, evidence_source, self_service_eligible, is_relevance_gate.
>
> Cilj ove faze: da mogućnost izmjene/dodavanja kriterija, workbookova i pondera bude potpuno dostupna kroz admin panel, bez ijedne linije koda — metodologija se očekivano još mijenja.

## 8. Faza 4 — Livewire wizard za unos audita

> Napravi Livewire komponentu "Novi audit" dostupnu auditoru (meni): korak 1 — izaberi ili kreiraj Company i popuni Profil kompanije; korak 2 — kreiraj Assessment (mode = full_audit, status = draft); korak 3 — provedi kroz sve workbookove redom, jedan po jedan.
>
> Za svaki workbook prikaži njegove kriterije grupisane po `group_label`. Za svaki kriterij: tekst pitanja, radio dugmad za opcije odgovora (label + implicitni bodovi, ali auditor NE vidi brojeve bodova dok popunjava — samo bira opis, tačno kao što metodologija zahtijeva), checkbox "N/A" sa poljem za razlog, upload za dokaz (screenshot), i tekstualno polje za napomenu.
>
> Implementiraj auto-save nakon svakog odgovora (status ostaje `in_progress`) tako da se audit može prekinuti i nastaviti kasnije — audit sastanci traju satima i rijetko se završe u jednom sjedenju. Kad auditor odgovori na `is_relevance_gate` kriterij negativno, automatski označi ostale kriterije iz iste grupe kao N/A i preskači ih u toku (uz mogućnost ručnog pregleda). Na kraju svih workbookova, dugme "Završi audit" mijenja status u `completed` i izračunava konačne rezultate preko ScoringService-a.

## 9. Faza 5 — Dashboard rezultata

> Napravi Livewire/Blade stranicu "Rezultati audita" za završen (ili djelimično popunjen) Assessment, vizuelno po uzoru na priloženi mockup:
> - Veliki kružni indikator ukupnog Reunion Digital Score-a (0–100) sa opisnim statusom (npr. Kritično/Reaktivno/Funkcionalno/Upravljano/Napredno, prema pragovima iz metodologije).
> - Bar chart raspodjele rezultata po glavnim oblastima.
> - Kartice po oblasti: kružni score, kratak opis, link "Pogledaj detalje" koji vodi na prikaz svih workbookova/kriterija te oblasti sa pojedinačnim odgovorima i dokazima.
> - Sekcije "Glavne snage", "Prioriteti za unapređenje" i "Ukupni potencijal rasta" — generisane iz kriterija sa najvišim/najnižim rezultatom i prioritetom (logika za ovo dolazi u Fazi 6).
>
> Koristi Chart.js (preko Alpine.js wrappera ili Livewire charts paketa po tvom izboru) za bar chart i kružne indikatore. Dizajn: čist, svijetel, sličan Google Lighthouse izvještaju — fokus na čitljivost brojeva, ne na dekoraciju.

## 10. Faza 6 — Akcioni plan i PDF izvoz

> Napravi `ActionPlanService` koji iz odgovora završenog Assessment-a generiše listu preporuka: za svaki kriterij gdje je ostvareno manje od punih bodova I prioritet je Kritičan ili Važan, kreiraj stavku preporuke (tekst kriterija + kolika je šteta bodova + workbook/oblast). Grupiši preporuke u vremenske horizonte: Kritičan prioritet → "0–14 dana"; Važan → "31–90 dana"; Preporučen → "3–12 mjeseci". Unutar svake grupe sortiraj po najvećem gubitku bodova prvo.
>
> Dodaj export dugme na dashboard koje generiše PDF izvještaj (koristi `spatie/laravel-pdf` ili `barryvdh/laravel-dompdf`) koji sadrži: ukupni score, rezultate po oblasti, top nalaze i akcioni plan — vizuelno konzistentan sa dashboardom, spreman za slanje klijentu ili štampanje na sastanku.

## 11. Backlog za nakon MVP-a

- Klijentski self-service "brzi scan" mod (85 kriterija već je označeno `self_service_eligible: true` u seed fajlu — spremno za filtriranje kad dođe vrijeme).
- Multi-tenant sloj, ako se RDS pretvori u nezavisan SaaS za druge agencije.
- Poređenje rezultata kroz vrijeme (ponovljeni audit iste kompanije) i grafici napretka.
- Kalibracija pondera na osnovu podataka iz prvih 5–10 stvarnih audita.
- Industrijski specifični Mystery Shopping scenariji (hotel, webshop, restoran...).

### Napomena o seed fajlu

`rds_methodology_seed.json` je generisan direktno iz svih Excel workbookova (uključujući najnovije verzije: Web v3 Master, Korisničko iskustvo v2, Digitalna efikasnost v2, svih 6 Marketing i rast podoblasti) — nije ručno prepisan, pa treba odražavati tačno ono što je u originalnim tabelama. Kad se promijene pitanja u Excelu, fajl treba regenerisati umjesto ručnog prepisivanja 420 redova.
