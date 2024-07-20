<?php
require_once 'database.php';
require_once 'model.php';

// We set a custom exception handler to handle exceptions and return JSON responses
// Refered from the example in lecture 30, slide 4
set_exception_handler(function ($e) {
    $code = $e->getCode() ?: 400;
    header("Content-Type: application/json", FALSE, $code);
    echo json_encode(array("error" => $e->getMessage()), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
});

// Creates a new instance of the Database class
$db = new Database();
// Creates the necessary tables and insert dummy data
$db->createTables();
$db->insertDummyData();

// Determines the resource from the request URI
// Refered from the example in lecture 29, slide 6
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$resourceStr = str_replace(dirname($scriptName) . '/', '', $requestUri);
$resource = trim($resourceStr, '/') === '' ? array() : explode('/', trim($resourceStr, '/'));

// If the resource is provided as a query parameter, using that instead
// Refered from the lecture 29, slide 7
if (array_key_exists('resource', $_REQUEST)) {
    $resourceFromQuery = $_REQUEST['resource'];
    $resource = $resourceFromQuery === '' ? array() : explode('/', $resourceFromQuery);
}

// we get the HTTP method of the request
$method = $_SERVER['REQUEST_METHOD'];

// Parses the input data from the request body
// this is refered from the example in lecture 30, slide 5
$inputData = json_decode(file_get_contents('php://input'), TRUE);

// Handles the request based on the HTTP method refered from the lecture 30 slide 10 (REST.PHP OUTLINE)
switch ($method) {
    case 'GET':
        [$data, $status] = handleGetRequest($db, $resource);
        break;
    case 'POST':
        [$data, $status] = handlePostRequest($db, $resource, $inputData);
        break;
    case 'PATCH':
        [$data, $status] = handlePatchRequest($db, $resource, $inputData);
        break;
    case 'DELETE':
        [$data, $status] = handleDeleteRequest($db, $resource);
        break;
    default:
        throw new Exception('Method Not Supported', 405);
}

// Reference: this is refered from the example in lecture 30, slide 6
header("Content-Type: application/json", TRUE, $status);
echo $data;

// Function for handlling GET requests
function handleGetRequest($db, $resource) {
    switch (count($resource)) {
        case 1:
            if ($resource[0] === 'teams') {
                return getTeams($db);
            } else {
                throw new Exception('Resource not found', 404);
            }
        case 2:
            if ($resource[0] === 'teams' && is_numeric($resource[1])) {
                $teamId = $resource[1];
                return getTeamDetails($db, $teamId);
            } else {
                throw new Exception('Resource not found', 404);
            }
        case 3:
            if ($resource[0] === 'teams' && is_numeric($resource[1]) && $resource[2] === 'players') {
                $teamId = $resource[1];
                return getTeamPlayers($db, $teamId);
            } else {
                throw new Exception('Resource not found', 404);
            }
        case 4:
            if ($resource[0] === 'teams' && is_numeric($resource[1]) && $resource[2] === 'players' && is_numeric($resource[3])) {
                $teamId = $resource[1];
                $playerId = $resource[3];
                return getPlayerDetails($db, $teamId, $playerId);
            } else {
                throw new Exception('Resource not found', 404);
            }
        default:
            throw new Exception('Resource not found', 404);
    }
}

// Function for handling POST requests
function handlePostRequest($db, $resource, $data) {
    if (count($resource) === 3 && $resource[0] === 'teams' && is_numeric($resource[1]) && $resource[2] === 'players') {
        $teamId = $resource[1];
        return addPlayer($db, $teamId, $data);
    } else {
        throw new Exception('Resource not found', 404);
    }
}

// Function for handling PATCH requests
function handlePatchRequest($db, $resource, $data) {
    if (count($resource) === 4 && $resource[0] === 'teams' && is_numeric($resource[1]) && $resource[2] === 'players' && is_numeric($resource[3])) {
        $teamId = $resource[1];
        $playerId = $resource[3];
        return updatePlayer($db, $teamId, $playerId, $data);
    } else {
        throw new Exception('Resource not found', 404);
    }
}

// Function for handling DELETE requests
function handleDeleteRequest($db, $resource) {
    if (count($resource) === 4 && $resource[0] === 'teams' && is_numeric($resource[1]) && $resource[2] === 'players' && is_numeric($resource[3])) {
        $teamId = $resource[1];
        $playerId = $resource[3];
        return deletePlayer($db, $teamId, $playerId);
    } else {
        throw new Exception('Resource not found', 404);
    }
}

// Function for retrieving all teams
function getTeams($db) {
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    // SQL query to retrieve teams and calculating the average age of players
    $sql = "SELECT t.*, FLOOR(AVG(DATEDIFF(CURDATE(), p.dob) / 365)) AS avg_age
            FROM teams AS t
            LEFT JOIN players AS p ON t.id = p.team_id
            GROUP BY t.id
            ORDER BY t.name";
    $stmt = $db->conn->query($sql);
    $teams = $stmt->fetchAll();

    $teamsData = array();

    foreach ($teams as $team) {
        $teamId = $team['id'];
        // Adds a hyperlink to the players of each team
        $team['players_url'] = $baseUrl . "/teams/$teamId/players";
        $t = new TeamModel($db->conn);
        $t->setData($team);
        // Sets the hyperlinks for the team
        // refered to the example in lecture 30, slide 23
        $t->setHyperlinks();
        $t->avg_age = $team['avg_age'];
        $teamsData[] = $t;
    }

    return [json_encode($teamsData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200];
}

// Function for retrieving players of a specific team
function getTeamPlayers($db, $teamId) {
    $stmt = $db->conn->prepare("SELECT * FROM players WHERE team_id = :teamId");
    $stmt->execute(['teamId' => $teamId]);
    $players = $stmt->fetchAll();

    return [json_encode($players, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200];
}

// Function for retrieving details of a specific player
function getPlayerDetails($db, $teamId, $playerId) {
    $stmt = $db->conn->prepare("SELECT * FROM players WHERE id = :playerId AND team_id = :teamId");
    $stmt->execute(['playerId' => $playerId, 'teamId' => $teamId]);
    $player = $stmt->fetch();

    if ($player) {
        return [json_encode($player, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200];
    } else {
        throw new Exception('Player not found', 404);
    }
}

// Function for retrieving details of a specific team
function getTeamDetails($db, $teamId) {
    $sql = "SELECT t.*, FLOOR(AVG(DATEDIFF(CURDATE(), p.dob) / 365.25)) AS avg_age
            FROM teams AS t
            LEFT JOIN players AS p ON t.id = p.team_id
            WHERE t.id = :teamId
            GROUP BY t.id";
    $stmt = $db->conn->prepare($sql);
    $stmt->execute(['teamId' => $teamId]);
    $team = $stmt->fetch();

    if ($team) {
        return [json_encode($team, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200];
    } else {
        throw new Exception('Team not found', 404);
    }
}

// Function for adding a new player
function addPlayer($db, $teamId, $data) {
    if (isset($data['surname']) && isset($data['given_names']) && isset($data['nationality']) && isset($data['dob'])) {
        $surname = trim($data['surname']);
        $givenNames = trim($data['given_names']);
        $nationality = trim($data['nationality']);
        $dob = trim($data['dob']);

        if (empty($surname) || empty($givenNames) || empty($nationality) || empty($dob)) {
            throw new Exception('Missing required parameters', 400);
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            throw new Exception('Invalid date of birth format', 400);
        } else {
            $stmt = $db->conn->prepare("SELECT id FROM players WHERE team_id = :teamId AND surname = :surname AND given_names = :givenNames AND nationality = :nationality AND dob = :dob");
            $stmt->execute(['teamId' => $teamId, 'surname' => $surname, 'givenNames' => $givenNames, 'nationality' => $nationality, 'dob' => $dob]);
            $existingPlayer = $stmt->fetch();

            if ($existingPlayer) {
                throw new Exception('Player already exists', 409);
            } else {
                $p = new PlayerModel($db->conn);
                $p->setData([
                    'team_id' => $teamId,
                    'surname' => $surname,
                    'given_names' => $givenNames,
                    'nationality' => $nationality,
                    'dob' => $dob
                ]);
                // Saves the player to the database
                // refered to the example in lecture 30, slide 24
                $p->saveToDatabase();
                // Set the hyperlinks for the player
                // refered to the example in lecture 30, slide 23
                $p->setHyperlinks();
                return [json_encode(['status' => 'success', 'message' => 'Player added successfully'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 201];
            }
        }
    } else {
        throw new Exception('Missing required parameters', 400);
    }
}


// Function for updating an existing player
function updatePlayer($db, $teamId, $playerId, $data) {
    $stmt = $db->conn->prepare("SELECT * FROM players WHERE id = :playerId AND team_id = :teamId");
    $stmt->execute(['playerId' => $playerId, 'teamId' => $teamId]);
    $existingPlayer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingPlayer) {
        $updateFields = [];
        $updateValues = [];

        if (isset($data['surname']) && $data['surname'] !== $existingPlayer['surname']) {
            $updateFields[] = "surname = :surname";
            $updateValues['surname'] = trim($data['surname']);
        }
        if (isset($data['given_names']) && $data['given_names'] !== $existingPlayer['given_names']) {
            $updateFields[] = "given_names = :givenNames";
            $updateValues['givenNames'] = trim($data['given_names']);
        }
        if (isset($data['nationality']) && $data['nationality'] !== $existingPlayer['nationality']) {
            $updateFields[] = "nationality = :nationality";
            $updateValues['nationality'] = trim($data['nationality']);
        }
        if (isset($data['dob']) && $data['dob'] !== $existingPlayer['dob']) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($data['dob']))) {
                throw new Exception('Invalid date of birth format', 400);
            }
            $updateFields[] = "dob = :dob";
            $updateValues['dob'] = trim($data['dob']);
        }

        if (!empty($updateFields)) {
            $updateQuery = "UPDATE players SET " . implode(", ", $updateFields) . " WHERE id = :playerId AND team_id = :teamId";
            $updateValues['playerId'] = $playerId;
            $updateValues['teamId'] = $teamId;

            $stmt = $db->conn->prepare($updateQuery);
            $stmt->execute($updateValues);

            return [json_encode(['status' => 'success', 'message' => 'Player updated successfully'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200];
        } else {
            return [json_encode(['status' => 'success', 'message' => 'No changes made'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200];
        }
    } else {
        throw new Exception('Player not found', 404);
    }
}

// Function for deleting a player
function deletePlayer($db, $teamId, $playerId) {
    $stmt = $db->conn->prepare("DELETE FROM players WHERE id = :playerId AND team_id = :teamId");
    $stmt->execute(['playerId' => $playerId, 'teamId' => $teamId]);

    if ($stmt->rowCount() > 0) {
        return [json_encode(array('status' => 'success', 'message' => 'Player deleted successfully'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200];
    } else {
        throw new Exception('Player not found', 404);
    }
}

// Function for handling successful responses
function handleSuccess($data) {
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}

// Function for handling error responses
function handleError($statusCode, $message) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode(array('status' => 'error', 'message' => $message), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}
?>