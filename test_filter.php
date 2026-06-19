<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'includes/profanity_filter.php';

$cases = [
    'Super lekcja, bardzo dziękuję!' => false,
    'Fajny film o fizyce' => false,
    'o rany, ale nuda' => false,
    'szuka' => false,
    'sukces' => false,
    'sz#uka' => false,
    'szukać' => false,
    's*uka' => true,
    's#uka' => true,
    'k*rwa' => true,
    
    // Polish vulgarisms
    'kurwa' => true,
    'O chuj' => true,
    'jebać' => true,
    'zjebać' => true,
    'wyjebany' => true,
    'skurwysyn' => true,
    'pierdolić' => true,
    'pizda' => true,
    
    // Obfuscations
    'k.u.r.w.a' => true,
    'ch-u-j' => true,
    'j3bać' => true,
    'k**wa' => true,
    'piździć' => true,
    
    // English vulgarisms
    'fuck you' => true,
    'son of a bitch' => true,
];

$allPassed = true;
foreach ($cases as $text => $expected) {
    $result = ProfanityFilter::hasProfanity($text);
    if ($result === $expected) {
        echo "[OK] '$text' -> " . ($result ? "blocked" : "allowed") . "\n";
    } else {
        echo "[FAIL] '$text' -> expected " . ($expected ? "blocked" : "allowed") . " but got " . ($result ? "blocked" : "allowed") . "\n";
        $allPassed = false;
    }
}

if ($allPassed) {
    echo "All tests passed successfully!\n";
} else {
    echo "Some tests failed!\n";
}
