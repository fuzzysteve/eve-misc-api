<?php
// JSON API for planetary interaction data: PI planet types, commodities,
// the planets in a system or a list of planets, and the Planet Finder system search.
//
//   planets.php?action=types
//   planets.php?action=commodities[&tier=0-4]
//   planets.php?action=system&system=<name or ID>
//   planets.php?action=planets&planets=<name or ID>,<name or ID>...
//       (names as in the SDE, "Jita 4", or as in the client, "Jita IV";
//       at most 100 per request)
//   planets.php?action=search&origin=<name or ID>[&types[]=<planetTypeID>...]
//       [&commodity[]=<typeID>...][&maxjumps=10][&route=1][&sec=high|low|null]
//       [&station=1][&limit=50]
//
// types and commodity also accept a comma-separated list (types=2016,2063).
// The query code is shared with the Planet Finder in routeplanner/planets.

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
$expires = 3600;
header('Cache-Control: public, max-age='.$expires);
header('Expires: '.gmdate('D, d M Y H:i:s', time() + $expires).' GMT');

require_once(__DIR__.'/../routeplanner/planets/common.php');

function out($data, $status = 200)
{
    http_response_code($status);
    if ($status >= 400) {
        header('Cache-Control: no-cache');
    }
    // Input echoed back (notFound, error text) may not be valid UTF-8; without
    // the substitute flag json_encode() returns false and the body is empty.
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function fail($message, $status = 400)
{
    out(array('error' => $message), $status);
}

// A list of integer IDs from types[]=1&types[]=2 or types=1,2.
function id_list($name)
{
    if (!isset($_GET[$name])) {
        return array();
    }
    $raw = is_array($_GET[$name]) ? $_GET[$name] : explode(',', $_GET[$name]);
    $ids = array();
    foreach ($raw as $v) {
        if (!is_string($v)) {
            fail("$name must be a list of numeric typeIDs.");
        }
        $v = trim($v);
        if ($v === '') {
            continue;
        }
        if (!ctype_digit($v)) {
            fail("$name must be a list of numeric typeIDs.");
        }
        if (!in_array((int)$v, $ids)) {
            $ids[] = (int)$v;
        }
    }
    return $ids;
}

function int_param($name, $default, $min, $max)
{
    if (!isset($_GET[$name]) || $_GET[$name] === '') {
        return $default;
    }
    if (!is_string($_GET[$name]) || !ctype_digit($_GET[$name])) {
        fail("$name must be a whole number.");
    }
    return max($min, min($max, (int)$_GET[$name]));
}

function system_json($s)
{
    return array(
        'solarSystemID' => (int)$s->solarSystemID,
        'solarSystemName' => $s->solarSystemName,
        'security' => round((float)$s->security, 4),
        'securityDisplay' => sec_display($s->security),
    );
}

// itemID, name, type, orbit, size, satellites and PI materials for a row from pi_system_planets().
function planet_json($p, $ptypes)
{
    $mats = array();
    if (isset($ptypes[(int)$p->typeID])) {
        foreach ($ptypes[(int)$p->typeID]['materials'] as $mid => $name) {
            $mats[] = array('typeID' => $mid, 'typeName' => $name);
        }
    }
    return array(
        'itemID' => (int)$p->itemID,
        'itemName' => $p->itemName,
        'celestialIndex' => (int)$p->celestialIndex,
        'typeID' => (int)$p->typeID,
        'typeName' => planet_type_name($p->typeName),
        'orbitAU' => round(planet_orbit_au($p), 3),
        'radiusKm' => (int)round($p->radius / 1000),
        'temperature' => $p->temperature === null ? null : round((float)$p->temperature, 1),
        'moons' => $p->moons,
        'belts' => $p->belts,
        'stations' => $p->stations,
        'piMaterials' => $mats,
    );
}

// "Jita IV" -> "Jita 4": the client shows planet numbers as Roman numerals, the SDE doesn't.
function planet_sde_name($name)
{
    if (!preg_match('/^(.*\S)\s+([IVXLC]+)$/i', $name, $m)) {
        return $name;
    }
    $values = array('C' => 100, 'L' => 50, 'X' => 10, 'V' => 5, 'I' => 1);
    $roman = strtoupper($m[2]);
    $n = 0;
    for ($i = 0; $i < strlen($roman); $i++) {
        $v = $values[$roman[$i]];
        $next = $i + 1 < strlen($roman) ? $values[$roman[$i + 1]] : 0;
        $n += $v < $next ? -$v : $v;
    }
    return $m[1].' '.$n;
}

$tiernames = array('P0', 'P1', 'P2', 'P3', 'P4');
$action = isset($_GET['action']) && is_string($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {

    case 'types':
        $out = array();
        foreach (pi_planet_types($dbh) as $id => $t) {
            $mats = array();
            foreach ($t['materials'] as $mid => $name) {
                $mats[] = array('typeID' => $mid, 'typeName' => $name);
            }
            $out[] = array('typeID' => $id, 'name' => $t['name'], 'materials' => $mats);
        }
        out($out);

    case 'commodities':
        $tier = null;
        if (isset($_GET['tier']) && $_GET['tier'] !== '') {
            if (!is_string($_GET['tier']) || !in_array($_GET['tier'], array('0', '1', '2', '3', '4'), true)) {
                fail('tier must be 0-4.');
            }
            $tier = (int)$_GET['tier'];
        }
        $ptypes = pi_planet_types($dbh);
        $sources = pi_sources($ptypes);
        $commodities = pi_commodities($dbh);
        $out = array();
        foreach ($commodities as $id => $c) {
            if ($tier !== null && $c['tier'] != $tier) {
                continue;
            }
            $row = array(
                'typeID' => $id,
                'typeName' => $c['name'],
                'tier' => $c['tier'],
                'tierName' => $tiernames[$c['tier']],
                'inputs' => array_values(array_unique($c['inputs'])),
                'rawMaterials' => array_keys(pi_raw_needs($commodities, $id)),
            );
            if ($c['tier'] == 0) {
                $row['planetTypes'] = isset($sources[$id]) ? $sources[$id] : array();
            }
            $out[] = $row;
        }
        out($out);

    case 'system':
        $key = isset($_GET['system']) && is_string($_GET['system']) ? trim($_GET['system']) : '';
        if ($key === '') {
            fail('system is required (a solar system name or ID).');
        }
        $system = pi_lookup_system($dbh, $key);
        if (!$system) {
            fail("Unknown system \"$key\".", 404);
        }
        $systemid = (int)$system->solarSystemID;
        $ptypes = pi_planet_types($dbh);
        $planets = array();
        foreach (pi_system_planets($dbh, $systemid) as $p) {
            $planets[] = planet_json($p, $ptypes);
        }
        out(system_json($system) + array(
            'constellationName' => $system->constellationName,
            'regionName' => $system->regionName,
            'star' => $system->star,
            'piAllowed' => !in_array($systemid, $pi_restricted_systems),
            'planets' => $planets,
        ));

    case 'planets':
        $raw = isset($_GET['planets']) ? $_GET['planets'] : '';
        $keys = array();
        foreach (is_array($raw) ? $raw : explode(',', $raw) as $k) {
            if (!is_string($k)) {
                fail('planets must be a list of planet names or itemIDs.');
            }
            $k = trim($k);
            if ($k !== '' && !in_array($k, $keys, true)) {
                $keys[] = $k;
            }
        }
        if (!$keys) {
            fail('planets is required: a comma-separated list of planet names or itemIDs.');
        }
        if (count($keys) > 100) {
            fail('At most 100 planets per request.');
        }

        // Match each key to a planet by itemID or by name (SDE or client form).
        $ids = array();
        $names = array();
        foreach ($keys as $k) {
            if (ctype_digit($k)) {
                $ids[] = (int)$k;
            } else {
                $names[] = $k;
                $names[] = planet_sde_name($k);
            }
        }
        $where = array();
        $args = array();
        if ($ids) {
            $where[] = 'd.itemID in ('.implode(',', array_fill(0, count($ids), '?')).')';
            $args = array_merge($args, $ids);
        }
        if ($names) {
            $where[] = 'd.itemName in ('.implode(',', array_fill(0, count($names), '?')).')';
            $args = array_merge($args, $names);
        }
        $stmt = $dbh->prepare('select d.itemID, d.itemName, d.solarSystemID from mapDenormalize d
                               where d.groupID=7 and ('.implode(' or ', $where).')');
        $stmt->execute($args);
        $byid = array();
        $byname = array();
        foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $row) {
            $byid[(int)$row->itemID] = (int)$row->solarSystemID;
            $byname[strtolower($row->itemName)] = (int)$row->itemID;
        }

        // Moons, belts and stations are counted per system, so load each system once.
        $ptypes = pi_planet_types($dbh);
        $systems = array();
        $sysplanets = array();
        $out = array();
        $notfound = array();
        foreach ($keys as $k) {
            if (ctype_digit($k)) {
                $itemid = isset($byid[(int)$k]) ? (int)$k : null;
            } else {
                $itemid = null;
                foreach (array($k, planet_sde_name($k)) as $n) {
                    if (isset($byname[strtolower($n)])) {
                        $itemid = $byname[strtolower($n)];
                        break;
                    }
                }
            }
            if ($itemid === null) {
                $notfound[] = $k;
                continue;
            }
            $sysid = $byid[$itemid];
            if (!isset($systems[$sysid])) {
                $systems[$sysid] = pi_lookup_system($dbh, $sysid);
                $sysplanets[$sysid] = pi_system_planets($dbh, $sysid);
            }
            $sys = $systems[$sysid];
            $out[] = planet_json($sysplanets[$sysid][$itemid], $ptypes) + array(
                'solarSystem' => system_json($sys) + array(
                    'constellationName' => $sys->constellationName,
                    'regionName' => $sys->regionName,
                    'piAllowed' => !in_array($sysid, $pi_restricted_systems),
                ),
            );
        }
        out(array('planets' => $out, 'notFound' => $notfound));

    case 'search':
        $key = isset($_GET['origin']) && is_string($_GET['origin']) ? trim($_GET['origin']) : '';
        if ($key === '') {
            fail('origin is required (a solar system name or ID).');
        }
        $origin = pi_lookup_system($dbh, $key);
        if (!$origin) {
            fail("Unknown starting system \"$key\".", 404);
        }
        $ptypes = pi_planet_types($dbh);
        $sources = pi_sources($ptypes);
        $commodities = pi_commodities($dbh);

        $wanted = id_list('types');
        foreach ($wanted as $id) {
            if (!isset($ptypes[$id])) {
                fail("$id isn't a PI planet typeID. See action=types.");
            }
        }
        $picked = id_list('commodity');
        $needs = array();
        foreach ($picked as $id) {
            if (!isset($commodities[$id])) {
                fail("$id isn't a PI commodity typeID. See action=commodities.");
            }
            pi_raw_needs($commodities, $id, $needs);
        }
        ksort($needs);

        $maxjumps = int_param('maxjumps', 10, 0, 50);
        $limit = int_param('limit', 50, 1, 200);
        $highroute = !empty($_GET['route']) && $_GET['route'] == 1;
        $sec = isset($_GET['sec']) && is_string($_GET['sec']) ? $_GET['sec'] : 'any';
        if (!in_array($sec, array('any', 'high', 'low', 'null'), true)) {
            fail('sec must be any, high, low or null.');
        }
        $station = !empty($_GET['station']);

        $found = pi_find_systems($dbh, $ptypes, (int)$origin->solarSystemID, $wanted, $needs, $maxjumps, $highroute, $sec, $station, $limit);
        if ($found['error']) {
            fail(str_replace('try "Through any security"', 'try route=0', $found['error']), 422);
        }

        $results = array();
        foreach ($found['results'] as $r) {
            $types = array();
            foreach ($ptypes as $id => $t) {
                $types[] = array('typeID' => $id, 'name' => $t['name'], 'count' => (int)$r->{"t$id"});
            }
            $mats = array();
            foreach ($needs as $mid => $x) {
                $c = 0;
                foreach ($sources[$mid] as $pid) {
                    $c += (int)$r->{"t$pid"};
                }
                $mats[] = array('typeID' => $mid, 'typeName' => $commodities[$mid]['name'], 'planets' => $c);
            }
            $results[] = system_json($r) + array(
                'regionName' => $r->regionName,
                'jumps' => (int)$r->jumps,
                'planets' => (int)$r->planets,
                'stations' => (int)$r->stations,
                'planetTypes' => $types,
                'rawMaterials' => $mats,
            );
        }
        $excluded = array();
        foreach ($found['excluded'] as $x) {
            $excluded[] = system_json($x);
        }
        out(array(
            'origin' => system_json($origin),
            'query' => array(
                'types' => $wanted,
                'commodities' => $picked,
                'rawMaterials' => array_keys($needs),
                'maxjumps' => $maxjumps,
                'highSecRoute' => $highroute,
                'sec' => $sec,
                'station' => $station,
                'limit' => $limit,
            ),
            'limitReached' => count($results) == $limit,
            'results' => $results,
            'excludedNoPI' => $excluded,
        ));

    default:
        fail('action must be one of: types, commodities, system, planets, search.');
    }
} catch (PDOException $e) {
    error_log('planets.php: '.$e->getMessage());
    fail('Database error.', 500);
}
