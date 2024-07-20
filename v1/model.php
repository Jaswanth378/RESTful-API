<?php

require_once 'database.php';

// The abstract Model class below is a reference from (the Student class) in lecture 30, slide 21 to slide 26
abstract class Model {
    protected $connection;
    protected static $tableName;
    protected $attributes;
    public $links;

    // Reference: The constructor here sets the database connection, as shown in lecture 30, slide 21
    public function __construct($dbc) {
        $this->connection = $dbc;
    }

    /* Reference:
    * The setData() method populates the model's attributes based on the provided data, 
    * which is similar to the set() method 
    * in lecture 30, slide 22
    */
    public function setData($source) {
        if (is_object($source))
            $source = (array)$source;

        foreach ($source as $key => $value) {
            if (in_array($key, $this->attributes))
                $this->$key = $value;
            elseif ($key === 'avg_age' || $key === 'players_url')
                continue;
            else
                throw new Exception("$key not an attribute of " . static::$tableName, 400);
        }
    }

    // The setHyperlinks() method below is an abstract method that should be implemented by the child classes to set the hyperlinks for the model
    abstract public function setHyperlinks();

    /*
     * Reference: The saveToDatabase() method saves the model's data to the database,
     * like the store() method in lecture 30, slide 24 
     */
    public function saveToDatabase() {
        $placeholders = implode(', ', array_fill(0, count($this->attributes) - 1, '?'));
        $query = 'INSERT INTO ' . static::$tableName . '(' . implode(', ', array_slice($this->attributes, 1)) . ') VALUES (' . $placeholders . ')';
        $statement = $this->connection->prepare($query);
        $values = array_map(function ($attr) {
            return $this->$attr;
        }, array_slice($this->attributes, 1));
        $statement->execute($values);
        $this->id = $this->connection->lastInsertId();
        return $this->id;
    }

    /* 
     * Reference: The loadFromDatabase() method loads the model's data from the database based on its ID, 
     * like the read() method in lecture 30, slide 26
     */
    public function loadFromDatabase() {
        $query = 'SELECT * FROM ' . static::$tableName . ' WHERE id = ?';
        $statement = $this->connection->prepare($query);
        $statement->execute(array($this->id));
        $row = $statement->fetch();
        foreach ($row as $key => $value)
            $this->$key = $value;
        $this->setHyperlinks();
    }

    /* Reference: The validateData() method validates if all the model's attributes are set, 
     *  similar to the validate() method in lecture 30, slide 25 
     */
    public function validateData() {
        foreach ($this->attributes as $key)
            if (is_null($this->$key))
                return FALSE;
        return TRUE;
    }

    /* Reference: The __toString() method returns the JSON representation of the model, 
       like the __toString() method in lecture 30, slide 24 */
    public function __toString() {
        return json_encode($this, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT);
    }
}

// The TeamModel class extends the Model class and represents a team
class TeamModel extends Model {
    protected static $tableName = 'teams';
    protected $attributes = ['id', 'name', 'sport'];
    public $id, $name, $sport;

    // The setHyperlinks() method below sets the hyperlinks for the team model refered from HATEOS Address class(3)
    public function setHyperlinks() {
        if ($this->id)
            $teamId = $this->id;
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $this->links = [
            (object)['url' => "$baseUrl/~sgjkattu/v1/teams/$teamId", 'method' => 'GET', 'rel' => 'self'],
            (object)['url' => "$baseUrl/~sgjkattu/v1/teams/$teamId", 'method' => 'PATCH', 'rel' => 'edit'],
            (object)['url' => "$baseUrl/~sgjkattu/v1/teams/$teamId", 'method' => 'DELETE', 'rel' => 'delete'],
            (object)['url' => "$baseUrl/~sgjkattu/v1/teams/$teamId/players", 'method' => 'GET', 'rel' => 'players']
        ];
    }
}

// The PlayerModel class extends the Model class and represents a player
class PlayerModel extends Model {
    protected static $tableName = 'players';
    protected $attributes = ['id', 'team_id', 'surname', 'given_names', 'nationality', 'dob'];
    public $id, $team_id, $surname, $given_names, $nationality, $dob;

    // The setHyperlinks() method below sets the hyperlinks for the player model
    public function setHyperlinks() {
        if ($this->id)
            $playerId = $this->id;
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $this->links = [
            (object)['url' => "$baseUrl/~sgjkattu/v1/teams/$this->team_id/players/$playerId", 'method' => 'GET', 'rel' => 'self'],
            (object)['url' => "$baseUrl/~sgjkattu/v1/teams/$this->team_id/players/$playerId", 'method' => 'PATCH', 'rel' => 'edit'],
            (object)['url' => "$baseUrl/~sgjkattu/v1/teams/$this->team_id/players/$playerId", 'method' => 'DELETE', 'rel' => 'delete']
        ];
    }
}

?>