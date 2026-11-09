<?php
/**
 * Generates a "white + dark green + black" colour override stylesheet from existing CSS.
 * White/grey neutrals are kept as they are; brand colours (blues, purples, teals) become
 * dark green, navy bands become black. Rules that set colour properties are re-emitted in
 * the original order, so the override file can be loaded after the originals.
 *
 * Usage: php generate.php <out.css> <extra.css|-> <in1.css> [in2.css ...]
 */

const BLACK = '#0b0b0d';        // replaces navy header/footer/sidebar bands
const INK = '#111111';          // replaces navy text
const TINT_BG = '#e9f5ee', TINT_BORDER = '#bfe3cc'; // replaces pale blue panels
// Accents: background colour and the text colour placed on it.
// Dark green and red are dark enough to be used for text on white as well.
const ACCENTS = [
    'green' => ['bg' => '#0f6b3a', 'on' => '#ffffff'], // primary brand colour
    'red'   => ['bg' => '#b3261e', 'on' => '#ffffff'], // errors, sale badges
];

$COLOR_PROPS = '/^(-webkit-|-moz-)?(color|background(-color|-image)?|border(-(top|right|bottom|left))?(-color)?|outline(-color)?|box-shadow|text-shadow|fill|stroke|caret-color|column-rule(-color)?|text-decoration-color|scrollbar-color)$/i';
$NAMED = ['white' => [255,255,255], 'black' => [0,0,0], 'red' => [255,0,0], 'gray' => [128,128,128], 'grey' => [128,128,128],
          'silver' => [192,192,192], 'whitesmoke' => [245,245,245], 'lightgray' => [211,211,211], 'lightgrey' => [211,211,211],
          'blue' => [0,0,255], 'green' => [0,128,0], 'orange' => [255,165,0], 'yellow' => [255,255,0], 'gainsboro' => [220,220,220],
          'aliceblue' => [240,248,255], 'ghostwhite' => [248,248,255], 'snow' => [255,250,250], 'ivory' => [255,255,240]];

function hsl($r, $g, $b) {
    $r /= 255; $g /= 255; $b /= 255;
    $max = max($r, $g, $b); $min = min($r, $g, $b); $l = ($max + $min) / 2; $d = $max - $min;
    if ($d == 0) return [0, 0, $l];
    $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
    if ($max == $r) $h = fmod(($g - $b) / $d + 6, 6); elseif ($max == $g) $h = ($b - $r) / $d + 2; else $h = ($r - $g) / $d + 4;
    return [$h * 60, $s, $l];
}

/** Map one colour to its theme equivalent for the given role (text|bg|border|shadow). */
function mapColor($r, $g, $b, $a, $role) {
    [$h, $s, $l] = hsl($r, $g, $b);
    // Shadows and white/grey/black neutrals stay as designed.
    if ($role === 'shadow' || $s < 0.18 || $l > 0.96 || $l < 0.06) return null;
    // Yellow/orange (rating stars, warnings) keep their meaning.
    if ($h >= 15 && $h < 70) return null;
    if ($l < 0.25) {                 // navy / very dark brand colours -> black
        $hex = $role === 'text' ? INK : BLACK;
    } elseif ($l > 0.85) {           // pale tints (light blue panels, etc.) -> pale green
        if ($role === 'text') return null;
        $hex = $role === 'bg' ? TINT_BG : TINT_BORDER;
    } else {                         // red stays red; every other hue becomes dark green
        $hex = ACCENTS[($h >= 340 || $h < 15) ? 'red' : 'green']['bg'];
    }
    return withAlpha($hex, $a);
}

/** If $hex is an accent background, return the text colour that sits on it. */
function onAccent($hex) {
    foreach (ACCENTS as $a) if (strcasecmp($a['bg'], $hex) === 0) return $a['on'];
    return null;
}

function withAlpha($hex, $a) {
    if ($a >= 0.999) return $hex;
    [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
    return sprintf('rgba(%d, %d, %d, %s)', $r, $g, $b, rtrim(rtrim(number_format($a, 3, '.', ''), '0'), '.'));
}

function roleOf($prop) {
    $p = strtolower(preg_replace('/^-(webkit|moz)-/', '', $prop));
    if (str_starts_with($p, 'background')) return 'bg';
    if (str_contains($p, 'shadow')) return 'shadow';
    if (str_starts_with($p, 'border') || str_starts_with($p, 'outline') || str_starts_with($p, 'column-rule')) return 'border';
    return 'text';
}

/** Replace every colour token in a declaration value. Returns [newValue, changed, mappedColours[]] */
function mapValue($value, $role) {
    global $NAMED;
    $mapped = [];
    $re = '/#([0-9a-f]{8}|[0-9a-f]{6}|[0-9a-f]{4}|[0-9a-f]{3})\b|rgba?\(([^)]*)\)|\b(' . implode('|', array_keys($NAMED)) . ')\b/i';
    $new = preg_replace_callback($re, function ($m) use ($role, &$mapped, $NAMED) {
        $a = 1.0;
        if (!empty($m[1])) {
            $x = $m[1];
            if (strlen($x) <= 4) $x = implode('', array_map(fn($c) => $c . $c, str_split($x)));
            $r = hexdec(substr($x, 0, 2)); $g = hexdec(substr($x, 2, 2)); $b = hexdec(substr($x, 4, 2));
            if (strlen($x) == 8) $a = hexdec(substr($x, 6, 2)) / 255;
        } elseif (isset($m[2]) && $m[2] !== '') {
            $parts = preg_split('/[\s,\/]+/', trim($m[2]));
            if (count($parts) < 3 || preg_match('/var\(|[a-z]/i', $m[2])) return $m[0];
            [$r, $g, $b] = array_map(fn($v) => str_ends_with($v, '%') ? (float)$v * 2.55 : (float)$v, array_slice($parts, 0, 3));
            if (isset($parts[3])) $a = str_ends_with($parts[3], '%') ? (float)$parts[3] / 100 : (float)$parts[3];
        } else {
            [$r, $g, $b] = $NAMED[strtolower($m[3])];
        }
        $out = mapColor($r, $g, $b, $a, $role);
        if ($out === null) return $m[0];
        $mapped[] = $out;
        return $out;
    }, $value);
    return [$new, $new !== $value, $mapped];
}

/** Split declarations on ';' outside parentheses/quotes. */
function splitDecls($body) {
    $out = []; $depth = 0; $q = null; $cur = '';
    for ($i = 0, $n = strlen($body); $i < $n; $i++) {
        $c = $body[$i];
        if ($q) { if ($c === $q && $body[$i - 1] !== '\\') $q = null; $cur .= $c; continue; }
        if ($c === '"' || $c === "'") $q = $c;
        elseif ($c === '(') $depth++;
        elseif ($c === ')') $depth--;
        elseif ($c === ';' && $depth === 0) { $out[] = $cur; $cur = ''; continue; }
        $cur .= $c;
    }
    if (trim($cur) !== '') $out[] = $cur;
    return $out;
}

function processRule($selector, $body) {
    global $COLOR_PROPS;
    $decls = []; $changed = false; $bgAccent = false; $hasColor = false;
    foreach (splitDecls($body) as $d) {
        if (!str_contains($d, ':')) continue;
        [$prop, $val] = array_map('trim', explode(':', $d, 2));
        if (!preg_match($COLOR_PROPS, $prop)) continue;
        $role = roleOf($prop);
        [$nv, $ch, $mapped] = mapValue($val, $role);
        $changed = $changed || $ch;
        if ($role === 'bg') foreach ($mapped as $m) if ($on = onAccent($m)) $bgAccent = $on;
        if (strtolower($prop) === 'color') $hasColor = count($decls);
        $decls[] = [$prop, $nv];
    }
    // Emit every rule that sets a colour property (even unchanged) so the original cascade order is kept.
    if (!$decls) return '';
    // Text sitting on an accent background gets that accent's readable text colour.
    if ($bgAccent) {
        if ($hasColor !== false) {
            $v = $decls[$hasColor][1];
            if (!preg_match('/inherit|transparent|currentcolor/i', $v)) $decls[$hasColor][1] = $bgAccent . (stripos($v, '!important') ? ' !important' : '');
        } else {
            $decls[] = ['color', $bgAccent];
        }
    }
    return trim($selector) . "{" . implode(';', array_map(fn($d) => $d[0] . ':' . $d[1], $decls)) . "}\n";
}

/** Walk a stylesheet, descending into @media/@supports, skipping other at-rules. */
function walk($css) {
    $out = ''; $i = 0; $n = strlen($css);
    while ($i < $n) {
        $open = strpos($css, '{', $i);
        if ($open === false) break;
        $prelude = trim(substr($css, $i, $open - $i));
        // drop stray statements like @charset/@import before the block
        $prelude = trim(preg_replace('/^(@(charset|import|namespace)[^;]*;\s*)+/i', '', $prelude));
        // find matching brace
        $depth = 1; $j = $open + 1;
        while ($j < $n && $depth) { if ($css[$j] === '{') $depth++; elseif ($css[$j] === '}') $depth--; $j++; }
        $inner = substr($css, $open + 1, $j - $open - 2);
        if (preg_match('/^@(media|supports)/i', $prelude)) {
            $sub = walk($inner);
            if ($sub !== '') $out .= $prelude . "{\n" . $sub . "}\n";
        } elseif ($prelude !== '' && $prelude[0] !== '@') {
            $out .= processRule($prelude, $inner);
        }
        $i = $j;
    }
    return $out;
}

[$_, $outFile, $extra] = $argv;
$files = array_slice($argv, 3);
$result = "/* Dracula theme override - generated from the original stylesheets; load it after them. */\n";
foreach ($files as $f) {
    $css = preg_replace('#/\*.*?\*/#s', '', file_get_contents($f));
    $body = walk($css);
    $result .= "\n/* ---- " . basename($f) . " ---- */\n" . $body;
    fwrite(STDERR, sprintf("%-40s %6d rules\n", basename($f), substr_count($body, '{')));
}
if ($extra !== '-') $result .= "\n/* ---- hand-written additions ---- */\n" . file_get_contents($extra);
file_put_contents($outFile, $result);
fwrite(STDERR, 'wrote ' . strlen($result) . " bytes to $outFile\n");
