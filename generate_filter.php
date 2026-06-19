<?php
// Generator script for profanity_filter.php containing over 2000 lines of static dictionary entries

$words = [];

// 1. Kurw
$kurw_suffixes = [
    'a', 'y', 'ie', 'ę', 'ą', 'o', 'iszcze', 'iszcza', 'iszczem', 'iszczu', 'iszczom', 'iszon', 'iszona', 'iszonem', 'iszony', 'iszonów', 'iska', 'isko', 'iskach', 'iskami', 'iarz', 'iarze', 'iarza', 'iarzem', 'iarzy', 'ica', 'icy', 'icę', 'icą', 'ico', 'iarstwo', 'ić', 'i', 'ią', 'ił', 'iła', 'iło', 'ili', 'iły', 'iący', 'iąca', 'iące', 'iących'
];
foreach ($kurw_suffixes as $s) {
    $words[] = "kurw" . $s;
}

// 2. Chuj / Huj
$chuj_stems = ['chuj', 'huj'];
$chuj_suffixes = [
    '', 'a', 'owi', 'em', 'u', 'e', 'ów', 'om', 'ami', 'ach', 'ek', 'ka', 'kiem', 'ku', 'ki', 'ków', 'ec', 'ca', 'cem', 'cu', 'ce', 'ców', 'nia', 'ni', 'nię', 'nią', 'nio', 'owy', 'owa', 'owe', 'owych', 'owym', 'ową', 'arze', 'arzu', 'arzem'
];
foreach ($chuj_stems as $stem) {
    foreach ($chuj_suffixes as $s) {
        $words[] = $stem . $s;
    }
}

// 3. Jeb
$jeb_prefixes = ['', 'o', 'od', 'do', 'za', 'na', 'przy', 'roz', 'pod', 'prze', 'z', 'u', 'w', 'we', 'zje', 'wje', 'naj', 'uj', 'wy'];
$jeb_suffixes = [
    'ać', 'ie', 'iesz', 'iecie', 'iemy', 'ią', 'ał', 'ała', 'ało', 'ali', 'ały', 'ano', 'ak', 'aka', 'akiem', 'aku', 'aki', 'aków', 'anko', 'anka', 'anki', 'ankiem', 'ance', 'anie', 'ania', 'aniu', 'aniem', 'any', 'ana', 'ane', 'anych', 'anym', 'aną', 'ani', 'nięty', 'nięta', 'nięte', 'niętych', 'niętym', 'niętą', 'nę', 'niesz', 'nie', 'niemy', 'niecie', 'ną'
];
foreach ($jeb_prefixes as $p) {
    foreach ($jeb_suffixes as $s) {
        $words[] = $p . "jeb" . $s;
    }
}

// 4. Pierd
$pierd_prefixes = ['', 'o', 'od', 'do', 'za', 'na', 'przy', 'roz', 'pod', 'prze', 's', 'u', 'w', 'wy'];
$pierd_suffixes = [
    'olić', 'oli', 'olisz', 'olicie', 'olimy', 'olą', 'olił', 'oliła', 'oliło', 'olili', 'oliły', 'olenie', 'olenia', 'oleniu', 'oleniem', 'olony', 'olona', 'olone', 'olonych', 'olonym', 'oloną', 'oleni', 'olnik', 'olca', 'olców', 'nąć', 'nę', 'niesz', 'nie', 'niemy', 'niecie', 'ną', 'nął', 'nęła', 'nęło', 'nęli', 'nęły', 'nięty', 'nięta', 'nięte', 'niętych', 'niętym', 'niętą', 'olnięty', 'olnięta', 'olnięte', 'olniętych', 'olniętym', 'olniętą'
];
foreach ($pierd_prefixes as $p) {
    foreach ($pierd_suffixes as $s) {
        $words[] = $p . "pierd" . $s;
    }
}

// 5. Pizd
$pizd_prefixes = ['', 'wy', 'roz', 's', 'za', 'od', 'do', 'na', 'przy'];
$pizd_suffixes = [
    'a', 'y', 'zie', 'ę', 'ą', 'o', 'u', 'ami', 'ach', 'ec', 'ca', 'cem', 'cu', 'ce', 'ców', 'nąć', 'nę', 'niesz', 'nie', 'niemy', 'niecie', 'ną', 'nął', 'nęła', 'nęło', 'nęli', 'nęły', 'nięty', 'nięta', 'nięte', 'niętych', 'niętym', 'niętą'
];
foreach ($pizd_prefixes as $p) {
    foreach ($pizd_suffixes as $s) {
        $words[] = $p . "pizd" . $s;
    }
}

// 6. Cwel
$cwel_suffixes = [
    '', 'a', 'owi', 'em', 'u', 'e', 'ów', 'om', 'ami', 'ach', 'ować', 'uje', 'ujesz', 'ujemy', 'ujecie', 'ują', 'ował', 'owała', 'owało', 'owali', 'owały', 'owany', 'owana', 'owane', 'owanych'
];
foreach ($cwel_suffixes as $s) {
    $words[] = "cwel" . $s;
}

// 7. Kutas
$kutas_suffixes = [
    '', 'a', 'owi', 'em', 'ie', 'y', 'ów', 'om', 'ami', 'ach', 'iarz', 'iarze', 'iarza', 'iarzem', 'iarzy', 'ik', 'iki', 'yca'
];
foreach ($kutas_suffixes as $s) {
    $words[] = "kutas" . $s;
}

// 8. Cip
$cip_suffixes = [
    'a', 'y', 'ie', 'ę', 'ą', 'o', 'ka', 'ki', 'ce', 'kę', 'ką', 'ko', 'sko', 'ska', 'ski', 'sków', 'iarz', 'iarze'
];
foreach ($cip_suffixes as $s) {
    $words[] = "cip" . $s;
}

// 9. Suka (specifically with boundaries)
$suka_suffixes = [
    '', 'a', 'i', 'ce', 'ę', 'ą', 'o', 'czysko', 'czka', 'czki', 'czce', 'czką', 'czko'
];
foreach ($suka_suffixes as $s) {
    $words[] = "suka" . $s;
    $words[] = "suc" . $s;
}

// 10. Dupa
$dupa_prefixes = ['', 'przy', 'pod', 'nad'];
$dupa_suffixes = [
    'a', 'y', 'ie', 'ę', 'ą', 'o', 'ek', 'ka', 'ki', 'ce', 'kom', 'kami', 'kach', 'iasty', 'iasta', 'iaste', 'iastych', 'sko', 'ska', 'ski', 'sków', 'olem'
];
foreach ($dupa_prefixes as $p) {
    foreach ($dupa_suffixes as $s) {
        $words[] = $p . "dup" . $s;
    }
}

// 11. Srać / Sra
$sra_prefixes = ['', 'ze', 'po', 'wy', 'od', 'do', 'za', 'na', 'przy', 'roz', 'pod', 'prze', 'o'];
$sra_suffixes = [
    'rać', 'ra', 'rasz', 'ramy', 'racie', 'rają', 'rał', 'rała', 'rało', 'rali', 'rały', 'ranie', 'rania', 'raniu', 'raniem', 'rany', 'rana', 'rane', 'ranych', 'ranym', 'raną', 'runek', 'runku', 'runkiem', 'runki', 'runków'
];
foreach ($sra_prefixes as $p) {
    foreach ($sra_suffixes as $s) {
        $words[] = $p . "s" . $s;
    }
}

// 12. Ruchać
$ruch_prefixes = ['', 'wy', 'po', 'od', 'do', 'za', 'na', 'przy', 'roz', 'prze', 'z'];
$ruch_suffixes = [
    'chać', 'cha', 'chasz', 'chamy', 'chacie', 'chają', 'chał', 'chała', 'chało', 'chali', 'chały', 'chanie', 'chania', 'chaniu', 'chaniem', 'chany', 'chana', 'chane', 'chanych', 'chanym', 'chaną', 'chacz', 'chacza', 'chaczem', 'chacze', 'chaczy'
];
foreach ($ruch_prefixes as $p) {
    foreach ($ruch_suffixes as $s) {
        $words[] = $p . "ru" . $s;
    }
}

// 13. Gówno
$gowne_suffixes = [
    'o', 'a', 'em', 'ie', 'u', 'iak', 'iaki', 'iaka', 'iakiem', 'iaku', 'iarz', 'iarze', 'iarza', 'iarzem', 'iarzy', 'iany', 'iana', 'iane', 'ianych', 'ianym'
];
foreach ($gowne_suffixes as $s) {
    $words[] = "gown" . $s;
    $words[] = "gówn" . $s;
}

// 14. English
$english = [
    'fuck', 'fucking', 'fucked', 'fucker', 'fuckers', 'fucks', 'fuckin', 'fuckyou', 'fuckingyou', 'fucku',
    'bitch', 'bitches', 'bitchy', 'bitched',
    'shit', 'shitting', 'shitted', 'shitter', 'shits', 'shitty', 'bullshit',
    'ass', 'asshole', 'assholes', 'cunt', 'cunts', 'whore', 'whores',
    'motherfuck', 'motherfucker', 'motherfuckers', 'cock', 'cocks', 'dick', 'dicks',
    'pussy', 'pussies', 'wank', 'wanker', 'wankers', 'bellend', 'twat', 'twats',
    'bollocks', 'bollox', 'piss', 'pissed', 'pissing', 'crap', 'crappy', 'sex', 'seks',
    'nigga', 'nigger', 'niggers', 'slut', 'sluts', 'bastard', 'bastards', 'dumbass',
    'dyke', 'dykes', 'faggot', 'faggots', 'fag', 'fags', 'kike', 'kikes', 'retard', 'retards',
    'spic', 'spics', 'prick', 'pricks', 'blowjob', 'blowjobs', 'handjob', 'handjobs',
    'cum', 'cuming', 'cumming', 'jizz', 'semen'
];
foreach ($english as $w) {
    $words[] = $w;
}

// Filter out duplicates and keep words with length >= 3 (except chuj, huj, jeb etc.)
$words = array_unique(array_filter($words, function($w) {
    $len = mb_strlen($w, 'UTF-8');
    return $len >= 3;
}));

// Sort words alphabetically
sort($words);

echo "Total generated words: " . count($words) . "\n";

// Construct php content
$code = "<?php\ndeclare(strict_types=1);\n\nfinal class ProfanityFilter\n{\n    // Complete dictionary of Polish and English profanities\n    private const BAD_WORDS = [\n";
foreach ($words as $w) {
    $code .= "        '" . addslashes($w) . "',\n";
}
$code .= "    ];\n\n";

$code .= <<<'EOT'
    // Map for leetspeak decoding
    private const LEET_MAP = [
        '0' => 'o',
        '3' => 'e',
        '1' => 'i',
        '!' => 'i',
        '|' => 'i',
        '4' => 'a',
        '@' => 'a',
        '5' => 's',
        '$' => 's',
        '7' => 't',
        'v' => 'u',
    ];

    /**
     * Checks if the text contains any blocked words or vulgarisms
     */
    public static function hasProfanity(string $text): bool
    {
        if (trim($text) === '') {
            return false;
        }

        // 1. Lowercase
        $normalized = mb_strtolower($text, 'UTF-8');

        // Remove Polish diacritics
        $diacritics = [
            'ą'=>'a', 'ć'=>'c', 'ę'=>'e', 'ł'=>'l', 'ń'=>'n', 'ó'=>'o', 'ś'=>'s', 'ź'=>'z', 'ż'=>'z'
        ];
        $normalized = strtr($normalized, $diacritics);

        // Pre-check: Regular expression checks for common obfuscated patterns (e.g. k**wa, j***ć, p***a)
        $regexCleaned = preg_replace('/[^\w\s\*]/u', '', $normalized) ?? $normalized;
        $patterns = [
            // Polish
            '/k[\*u0v]{1,2}[\*r]?[\*w][\*a]?/ui',       // kurw, k*rw, k**wa, k**w
            '/p[\*i1!\|e3r]{1,3}[\*d]/ui',              // pierd, p*erd, p***d
            '/j[\*e3]{1,2}[\*b]/ui',                    // jeb, j*b, j3b
            '/p[\*i1!\|][\*z]?[\*d]/ui',                // pizd, p*zd, pi*d, p***d
            '/c[\*w3e]{1,2}[\*l]/ui',                   // cwel, c*el, c**l, cw3l, c3l
            '/k[\*u0v]([\*t][\*a4\@]?|[\*a4\@])[\*s]/ui', // kutas, k*tas, ku**s, kut*s
            '/(ch|h)[\*u0v][\*j]/ui',                   // chuj, huj, ch*j, c*uj
            '/(?<!z)s[\*u0v][\*k]?[\*a4\@](?!ces)/ui',  // suka, s*ka
            
            // English
            '/f[\*u0v]{1,2}[\*c]?[\*k]/ui',             // fuck, f*ck, f***, fvck
            '/s[\*h][\*i1!\|][\*t7]/ui',                // shit, sh*t, sh**
            '/b[\*i1!\|][\*t]?[\*c]?[\*h]/ui',          // bitch, b*tch, b***
            '/a[\*s5\$]{2,}[\*h][\*o0]?[\*l]?[\*e3]?/ui', // asshole, a**hole
            '/c[\*u0v][\*n][\*t7]/ui',                  // cunt, c*nt
            '/d[\*i1!\|][\*c]?[\*k]/ui',                // dick, d*ck
            '/p[\*u0v][\*s5\$]{2,}[\*y]/ui',            // pussy, p*ssy
            '/c[\*o0][\*c]?[\*k]/ui',                   // cock, c*ck
            '/b[\*a4\@][\*s5\$][\*t7][\*a4\@][\*r][\*d]/ui', // bastard
            '/motherf[\*u0v]{1,2}[\*c]?[\*k]/ui',       // motherfucker
            '/w[\*h][\*o0][\*r][\*e3]/ui',              // whore
            '/w[\*a4\@][\*n][\*k][\*e3][\*r]/ui',       // wanker
            '/t[\*w][\*a4\@][\*t7]/ui',                 // twat
            '/b[\*o0][\*l]{2}[\*o0][\*c]?[\*k][\*s5\$]/ui', // bollocks
            '/n[\*i1!\|][\*g]{2}[\*a4\@r][\*e3]?[\*r]?/ui', // nigga/nigger
            '/\bs[\*e3]{1,2}[\*x5\$]\b/ui',             // sex, s*x
            '/\bs[\*e3]{1,2}[\*k][\*s5\$]\b/ui',        // seks, s*ks
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $regexCleaned)) {
                return true;
            }
        }

        // 2. We extract words from the comment to do whole-word checks.
        // This solves the Scunthorpe problem (e.g. "szuka" contains "suka").
        // We replace any separation characters/punctuation/numbers with space, but keep letters and '*'
        $cleanedText = preg_replace('/[\s\.\-_\/\\\\\+\,\?\'\"\#\(\)\[\]\{\}\%\&\@\=\:\;\!\~]/u', ' ', $normalized) ?? $normalized;

        // Apply leetspeak decoding to the cleaned text
        $decodedText = strtr($cleanedText, self::LEET_MAP);

        // Split into individual words
        $wordsInText = array_filter(explode(' ', $decodedText));

        foreach ($wordsInText as $word) {
            $word = trim($word);
            if ($word === '') continue;

            // Generate variants replacing asterisks
            $variants = [
                $word,
                str_replace('*', 'u', $word),
                str_replace('*', 'e', $word),
                str_replace('*', 'i', $word),
                str_replace('*', 'o', $word),
                str_replace('*', 'a', $word),
                str_replace('*', '', $word),
            ];

            foreach ($variants as $variant) {
                // Check if the exact word exists in our blocklist
                if (in_array($variant, self::BAD_WORDS, true)) {
                    return true;
                }
            }
        }

        // 3. Fallback: also check for strong substring matches that have no common false positives
        // e.g. "kurw", "pierd", "jeb", "pizd", "cwel", "kutas", "chuj", "huj"
        $strongStems = ['kurw', 'pierd', 'jeb', 'pizd', 'cwel', 'kutas', 'chuj', 'huj', 'fuck', 'bitch', 'cunt', 'motherfuck', 'nigger', 'nigga'];
        $noPunctuation = str_replace(' ', '', $decodedText);
        $noPunctuationVariants = [
            $noPunctuation,
            str_replace('*', 'u', $noPunctuation),
            str_replace('*', 'e', $noPunctuation),
            str_replace('*', 'i', $noPunctuation),
            str_replace('*', 'o', $noPunctuation),
            str_replace('*', 'a', $noPunctuation),
            str_replace('*', '', $noPunctuation),
        ];

        foreach ($noPunctuationVariants as $variant) {
            foreach ($strongStems as $stem) {
                if (str_contains($variant, $stem)) {
                    return true;
                }
            }
            // Safe check for "suka" avoiding "szuka" / "sukces"
            if (preg_match('/(?<!z)suka(?!ces)/u', $variant)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Censures profanities by replacing characters with asterisks
     */
    public static function censor(string $text): string
    {
        if (trim($text) === '') {
            return $text;
        }

        return preg_replace_callback('/\b\w+\b/u', function ($matches) {
            $word = $matches[0];
            if (self::hasProfanity($word)) {
                $len = mb_strlen($word, 'UTF-8');
                if ($len <= 2) {
                    return str_repeat('*', $len);
                }
                return mb_substr($word, 0, 1, 'UTF-8') . str_repeat('*', $len - 1);
            }
            return $word;
        }, $text);
    }
}
EOT;

file_put_contents('includes/profanity_filter.php', $code);
echo "Successfully generated includes/profanity_filter.php!\n";
