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

In `config/bundles.php`, se non c'è già:

```php
K3Progetti\SoftwareVersionBundle\SoftwareVersionBundle::class => ['all' => true],
```

Nel `.env` (o nelle variabili del container) la chiave della tua azienda:

```dotenv
SOFTWARE_VERSION_KEY=svk_...
```

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

## ⏱️ Quando mandarlo

- **All'avvio del container**, così il server sa quando è stato fatto il rilascio. Nell'entrypoint:

  ```bash
  php bin/console software-version:send-info || true
  ```

  Il `|| true` è voluto: se il server delle versioni non risponde il deploy deve andare avanti lo stesso.

- **Una volta al giorno** da cron, per sapere che il progetto è ancora vivo.

Il progetto viene riconosciuto dal `name` del `composer.json` (senza `template/` davanti), lo stesso
che usa la sincronizzazione da GitHub.

---

## 🧰 Comandi

| Comando | Cosa fa |
|---|---|
| `software-version:send-info` | Manda PHP e Symfony al server. Senza `SOFTWARE_VERSION_KEY` non fa niente |
| `software-version:get-composer-info` | Mostra cosa manderebbe, senza mandarlo |
