# Sports Teams and Players Management System 

This system is a RESTful API for managing sports teams and their players. It provides endpoints for retrieving, adding, updating, and deleting player information, as well as retrieving team information.

## Features

- Retrieve information on all teams
- Retrieve information on a specific team
- Retrieve information on all players of a team
- Retrieve information on a specific player of a team
- Add a new player to a team
- Update information of an existing player
- Delete a player from a team

## Technologies Used

- PHP
- MySQL
- PDO for database connections
- JSON for data interchange

## Setup

1. Ensure you have PHP and MySQL installed on your server.
2. Create a MySQL database named "sports".
3. Update the database connection details in `database.php` if necessary.
4. Place all PHP files in your web server's document root.
5. Ensure the `.htaccess` file is in the same directory as the PHP files.

## File Structure

- `database.php`: Handles database connection and initial setup
- `model.php`: Contains the Model, TeamModel, and PlayerModel classes
- `rest.php`: Main file handling API requests and responses
- `.htaccess`: Apache configuration file for URL rewriting
- `interface.html`: HTML interface for interacting with the API

## API Endpoints

- GET `/teams`: Retrieve all teams
- GET `/teams/{teamId}`: Retrieve a specific team
- GET `/teams/{teamId}/players`: Retrieve all players of a team
- GET `/teams/{teamId}/players/{playerId}`: Retrieve a specific player
- POST `/teams/{teamId}/players`: Add a new player to a team
- PATCH `/teams/{teamId}/players/{playerId}`: Update a player's information
- DELETE `/teams/{teamId}/players/{playerId}`: Delete a player from a team

## Usage

1. Access the system through your web browser using the `interface.html` file.
2. Use the interface to send requests to the API and view responses.

### Sample Request Bodies

Adding a player:
```json
{
  "surname": "MS",
  "given_names": "Dhoni",
  "nationality": "Indian",
  "dob": "1981-07-07"
}
```

Updating a player:
```json
{
  "surname": "Raina",
  "given_names": "Suresh",
  "nationality": "British",
  "dob": "1986-11-27"
}
```

## Error Handling

The system uses custom exception handling to return appropriate error messages and HTTP status codes for various scenarios.

## Security Considerations

- The system uses prepared statements to prevent SQL injection.
- Input validation is implemented for data integrity.
- Consider implementing authentication and authorization for a production environment.

## Future Improvements

- Implement pagination for large datasets
- Add more detailed validation for player information
- Implement caching for frequently accessed data
- Add unit tests for better code reliability

For any issues or suggestions, please contact the system administrator.
