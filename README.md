# Software Version Bundle

Manda al server delle versioni (l'app **Versioni software**) cosa gira **davvero** nel progetto:
il PHP del processo (`PHP_VERSION`) e il Symfony installato in `vendor`.

GitHub dice com'è il codice e Docker Hub com'è l'immagine adesso, ma solo il progetto sa cosa è
stato rilasciato: un'immagine ricompilata e non ancora rilasciata non cambia niente sul server.

---

## ✅ Requisiti

Apposta larghi, perché serve soprattutto sui progetti vecchi:

- PHP >= 7.2.5 con `ext-curl` ed `ext-json`
- Symfony 5.4, 6.x, 7.x o 8.x

---

## 🚀 Installazione

```bash
composer require k3progetti/software-version-bundle
```

Composer chiede se fidarsi del plugin del bundle: rispondi `y` (finisce in `allow-plugins` del
`composer.json`, da committare). Il plugin poi:

- aggiunge il bundle in `config/bundles.php` e crea `config/packages/software_version.yaml`;
- se nel `.env` manca `SOFTWARE_VERSION_KEY`, **chiede la password** e, se è giusta, ci scrive la chiave
  dell'azienda. Invio a vuoto per saltare. Senza terminale (docker build, CI) non chiede niente.

Se non hai la password, la chiave la metti a mano nel `.env` (o nelle variabili del container):

```dotenv
SOFTWARE_VERSION_KEY=svk_...
```

> Nei progetti con Composer 2.2+ che fanno `composer install -n` (Dockerfile, CI) il plugin deve essere
> già in `allow-plugins`, se no Composer si ferma:
>
> ```json
> "config": { "allow-plugins": { "k3progetti/software-version-bundle": true } }
> ```

### Chiave cifrata nel bundle

Il repo è pubblico, quindi la chiave sta in `resources/software_version_key.json` cifrata con la password
(AES-256-GCM, PBKDF2 a 600.000 giri). Per cambiare chiave o password, nel repo del bundle:

```bash
php bin/encrypt-key.php
```

e poi una release. Usa una password lunga: il file lo può scaricare chiunque e provare password offline.

La chiave è una per azienda e la trovi sul server delle versioni:

```bash
php bin/console software-version:company-key <id-azienda>
```

> Non è `APP_SECRET`: quello non deve uscire dal progetto. Se un giorno la chiave va cambiata
> (`--new`), quella vecchia smette di funzionare per tutti i progetti dell'azienda.

### Configurazione (facoltativa)

Serve solo per cambiare l'indirizzo del server, `config/packages/software_version.yaml`:

```yaml
software_version:
  endpoint: 'https://api.software-version.k3progetti.it/public/software'
```

---

## ⏱️ Quando manda

**Da solo, non serve nessun cron.** Dopo una richiesta HTTP, quando la risposta è già partita
(`kernel.terminate`), manda i dati al massimo una volta all'ora. Il segno dell'ultimo invio sta nella
cache di Symfony (`var/cache/<env>/software_version_sent`): col deploy la cache riparte vuota e la prima
richiesta manda subito, così sul server delle versioni la data di rilascio è giusta.

Il progetto non se ne accorge: timeout di 5 secondi, errori ignorati, e se il server delle versioni è giù
riprova all'intervallo dopo, non a ogni richiesta.

Per cambiare l'intervallo o spegnerlo, `config/packages/software_version.yaml`:

```yaml
software_version:
  auto_send: true   # false per spegnerlo
  interval: 3600    # secondi, minimo 60
```

Il comando `software-version:send-info` resta per provare a mano e per i progetti **senza richieste web**
(solo worker o comandi): lì va messo in un job di Ofelia, per esempio ogni ora.

Il progetto viene riconosciuto dal `name` del `composer.json` (senza `template/` davanti), lo stesso
che usa la sincronizzazione da GitHub.

---

## 🧰 Comandi

| Comando | Cosa fa |
|---|---|
| `software-version:send-info` | Manda subito PHP e Symfony al server. Senza `SOFTWARE_VERSION_KEY` non fa niente |
| `software-version:get-composer-info` | Mostra cosa manderebbe, senza mandarlo |

---

## ⬆️ Dalla 2.x alla 3.0

- `app_secret` non c'è più: la 2.x mandava fuori l'`APP_SECRET` del progetto, la 3.0 usa la chiave
  dell'azienda in `SOFTWARE_VERSION_KEY`. Togli `app_secret` da `config/packages/software_version.yaml`.
- Manda solo `name`, `phpVersion` e `symfonyVersion`: il resto (bundle, requisiti, cliente) lo legge già
  la sincronizzazione da GitHub.
- Requisiti allargati: si installa anche sui progetti vecchi (PHP 7.2.5+, Symfony 5.4+).

## 🧪 Provato su

Comando e invio automatico dopo una richiesta (la seconda entro l'ora non manda):

- PHP 7.2.34 + Symfony 5.4.54
- PHP 8.4.27 + Symfony 8.1.9
