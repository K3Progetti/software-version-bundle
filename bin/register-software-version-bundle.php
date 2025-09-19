#!/usr/bin/env php
<?php

$projectRoot = getcwd();
$bundlesFile = $projectRoot . '/config/bundles.php';
$bundleClass = 'K3Progetti\SoftwareVersionBundle\SoftwareVersionBundle::class';
$bundleLine = "    $bundleClass => ['all' => true],";


function green($text): string
{
    return "\033[32m$text\033[0m";
}

function yellow($text): string
{
    return "\033[33m$text\033[0m";
}

function red($text): string
{
    return "\033[31m$text\033[0m";
}

echo yellow("🔍 File bundles: $bundlesFile\n");

if (!file_exists($bundlesFile)) {
    echo red("❌ File config/bundles.php non trovato.\n");
    exit(1);
}

$contents = file_get_contents($bundlesFile);
$argv = $_SERVER['argv'];
$remove = in_array('--remove', $argv, true);

if ($remove) {
    // Rimozione bundle
    if (strpos($contents, $bundleLine) !== false) {
        $contents = str_replace($bundleLine . "\n", '', $contents);
        $contents = str_replace($bundleLine, '', $contents); // fallback
        file_put_contents($bundlesFile, $contents);
        echo green("🗑️  SoftwareVersionBundle rimosso da config/bundles.php\n");
    } else {
        echo yellow("ℹ️  SoftwareVersionBundle non presente in config/bundles.php\n");
    }
} else {
    // Aggiungo bundle
    if (strpos($contents, $bundleClass) === false) {
        $pattern = '/(return\s+\[\n)(.*?)(\n\];)/s';
        if (preg_match($pattern, $contents, $matches)) {
            $before = $matches[1];
            $middle = rtrim($matches[2]);
            $after = $matches[3];

            $newMiddle = $middle . "\n" . $bundleLine;
            $newContents = $before . $newMiddle . $after;
            file_put_contents($bundlesFile, $newContents);
            echo green("✅ SoftwareVersionBundle aggiunto in fondo a config/bundles.php\n");
        } else {
            echo red("❌ Errore durante l'inserimento in config/bundles.php\n");
        }
    } else {
        echo yellow("ℹ️  SoftwareVersionBundle è già presente in config/bundles.php\n");
    }

}
