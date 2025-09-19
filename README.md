# Software Version

Bundle Symfony per la gestione avanzata dei token JWT, con supporto a:

- Login e generazione token JWT
- Refresh token
- Logout e invalidazione dei token
- Comandi da terminale per la pulizia dei token scaduti

---

## ✅ Requisiti

- PHP >= 8.2
- Symfony 7.0

---

## 🚀 Installazione

```bash
composer require k3progetti/jwt-bundle
```

```bash
php composer.phar install --ignore-platform-req=ext-redis
```

---

## ⚙️ Configurazione

### 📦 Registrazione del bundle

Aggiungi il bundle al tuo `config/bundles.php` se non viene registrato automaticamente:

```php
return [
    // ...
    K3Progetti\JwtBundle\SoftwareVersionBundle::class => ['all' => true],
];
```

### 🔐 Configurazione del firewall (`config/packages/security.yaml`)

```yaml
firewalls:
    api:
        pattern: ^/api/
        stateless: true
        custom_authenticator: K3Progetti\JwtBundle\Security\JwtAuthenticator
```

---

### 🧱 Migrazioni

Il bundle include due entità: `JwtToken` e `JwtRefreshToken`.  
Dopo aver installato il bundle, **genera e applica le migrazioni**:

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

## 🧭 Struttura del Progetto

```
JwtBundle/
├── JwtBundle.php
├── bin/
│   └── register-jwt-bundle.php
├── resources/
│   └── config/
│       └── services.yaml
├── src/
│   ├── Command/
│   │   ├── RemoveJwtRefreshTokenExpired.php
│   │   ├── RemoveJwtTokenExpired.php
│   │   └── RemoveJwtTokenUser.php
│   ├── Controller/
│   │   └── AuthController.php
│   ├── DependencyInjection/
│   │   ├── Configuration.php
│   │   └── JwtExtension.php
│   ├── Entity/
│   │   ├── JwtToken.php
│   │   └── JwtRefreshToken.php
│   ├── Event/
│   │   └── JwtUserLoggedOutEvent.php
│   ├── Helper/
│   │   └── AuthHelper.php
│   ├── Repository/
│   │   ├── JwtTokenRepository.php
│   │   └── JwtRefreshTokenRepository.php
│   ├── Security/
│   │   ├── JwtAuthenticator.php
│   │   └── Handler/
│   │       ├── LoginHandler.php
│   │       ├── LogoutHandler.php
│   │       └── RefreshTokenHandler.php
│   └── Service/
│       ├── JwtService.php
│       └── JwtRefreshService.php
```

---

## 🔧 Comandi Console Disponibili

```bash
bin/console jwt:remove-jwt-refresh-token-expired
bin/console jwt:remove-jwt-token-expired
bin/console jwt:remove-jwt-token-user
```

---

## 🤝 Contributi

Sono aperto a qualsiasi confronto.