#!/usr/bin/env php
<?php

// Cifra la chiave dell'azienda con la password e la salva in resources/software_version_key.json.
// Da lanciare a mano nel repo del bundle quando cambia la chiave o la password, poi si fa una release

require __DIR__ . '/../src/Composer/KeyCrypto.php';

use K3Progetti\SoftwareVersionBundle\Composer\KeyCrypto;

function ask(string $question): string
{
    echo $question;
    // Nascondo quello che scrivo
    shell_exec('stty -echo');
    $answer = trim((string)fgets(STDIN));
    shell_exec('stty echo');
    echo "\n";

    return $answer;
}

$key = ask('Chiave (svk_...): ');
if (strpos($key, 'svk_') !== 0) {
    fwrite(STDERR, "La chiave deve iniziare con svk_\n");
    exit(1);
}
$password = ask('Password: ');
if (strlen($password) < 12) {
    fwrite(STDERR, "Almeno 12 caratteri: il file è pubblico e la password si può provare offline\n");
    exit(1);
}
if (ask('Ripeti la password: ') !== $password) {
    fwrite(STDERR, "Le password non sono uguali\n");
    exit(1);
}

$encrypted = KeyCrypto::encrypt($key, $password);
// Controllo che si decifri prima di salvare
if (KeyCrypto::decrypt($encrypted, $password) !== $key) {
    fwrite(STDERR, "Qualcosa non va con la cifratura, non salvo\n");
    exit(1);
}
file_put_contents(KeyCrypto::FILE, json_encode($encrypted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "Salvata in resources/software_version_key.json\n";
