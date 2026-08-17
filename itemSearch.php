<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once('db.inc.php');

$name = $_GET['term'] ?? '';
if (strlen($name) < 2) {
    echo json_encode(array());
    exit;
}
$likename = "%$name%";

$sql = <<<EOS
SELECT typeName, typeid
FROM invTypes
WHERE published = 1
AND typeName LIKE ?
ORDER BY instr(typeName, ?)
LIMIT 15
EOS;

$stmt = $dbh->prepare($sql);
$stmt->execute(array($likename, $name));
$results = array();
while ($row = $stmt->fetchObject()) {
    $results[] = array('id' => (int)$row->typeid, 'label' => $row->typeName, 'value' => $row->typeName);
}

echo json_encode($results);
